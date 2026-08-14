<?php

namespace Tests\Feature;

use App\Models\User;
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

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
