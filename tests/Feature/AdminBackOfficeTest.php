<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Address;
use App\Models\Building;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Auth\Access\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionRegistrar::class)->registerPermissions(app(Gate::class));
    }

    public function test_guest_cannot_access_the_admin_area(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_without_admin_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_the_back_office(): void
    {
        $admin = $this->admin();
        $this->assertTrue($admin->can('access admin'));

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Vue d’ensemble');
    }

    public function test_admin_can_create_a_building(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/buildings', [
                'reference' => 'IMMEUBLE-01',
                'name' => 'Résidence des Tilleuls',
                'address' => [
                    'line1' => '10 rue des Tilleuls',
                    'postal_code' => '75001',
                    'city' => 'Paris',
                    'country' => 'FR',
                ],
            ])
            ->assertRedirect('/admin/buildings');

        $this->assertDatabaseHas('buildings', [
            'reference' => 'IMMEUBLE-01',
            'name' => 'Résidence des Tilleuls',
        ]);
        $this->assertDatabaseHas('addresses', [
            'line1' => '10 rue des Tilleuls',
            'city' => 'Paris',
        ]);
    }

    public function test_admin_can_create_a_house_with_its_own_address(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/properties', [
                'reference' => 'MAISON-01',
                'name' => 'Maison de campagne',
                'type' => 'house',
                'status' => 'active',
                'address' => [
                    'line1' => '5 chemin du Lac',
                    'postal_code' => '33000',
                    'city' => 'Bordeaux',
                    'country' => 'FR',
                ],
            ])
            ->assertRedirect('/admin/properties');

        $this->assertDatabaseHas('properties', [
            'reference' => 'MAISON-01',
            'type' => 'house',
            'building_id' => null,
        ]);
    }

    public function test_apartment_requires_a_building(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/properties', [
                'reference' => 'APPART-01',
                'name' => 'Appartement sans immeuble',
                'type' => 'apartment',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('building_id');
    }

    public function test_admin_can_view_update_and_delete_a_property(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'APPART-01',
            'name' => 'Appartement test',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.properties.show', $property))
            ->assertOk()
            ->assertSee($property->reference);

        $this->actingAs($admin)
            ->put(route('admin.properties.update', $property), [
                'reference' => 'APPART-MODIFIE',
                'name' => 'Appartement modifie',
                'type' => Property::TYPE_APARTMENT,
                'building_id' => $building->id,
                'status' => 'inactive',
            ])
            ->assertRedirect('/admin/properties');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'reference' => 'APPART-MODIFIE',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.properties.destroy', $property))
            ->assertRedirect('/admin/properties');

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_building_with_properties_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();
        Property::create([
            'reference' => 'APPART-02',
            'name' => 'Appartement test',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.buildings.destroy', $building))
            ->assertRedirect('/admin/buildings')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('buildings', ['id' => $building->id]);
    }

    public function test_property_form_contains_dynamic_address_and_building_sections(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.properties.create'));

        $response->assertOk()
            ->assertSee('id="building-fields"', false)
            ->assertSee('id="address-fields"', false)
            ->assertSee('function updatePropertyFields', false);
    }

    public function test_changing_a_house_to_an_apartment_detaches_and_deletes_its_address(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();
        $address = Address::create([
            'line1' => '10 avenue de la Maison',
            'postal_code' => '69001',
            'city' => 'Lyon',
            'country' => 'FR',
        ]);
        $property = Property::create([
            'reference' => 'MAISON-TRANSITION',
            'name' => 'Maison à transformer',
            'type' => Property::TYPE_HOUSE,
            'address_id' => $address->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.properties.update', $property), [
                'reference' => $property->reference,
                'name' => $property->name,
                'type' => Property::TYPE_APARTMENT,
                'building_id' => $building->id,
                'status' => 'active',
            ])
            ->assertRedirect('/admin/properties');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'address_id' => null,
        ]);
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createBuilding(): Building
    {
        $address = Address::create([
            'line1' => '1 rue du Test',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);

        return Building::create([
            'reference' => 'IMMEUBLE-TEST',
            'name' => 'Immeuble test',
            'address_id' => $address->id,
        ]);
    }
}
