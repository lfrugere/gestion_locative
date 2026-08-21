<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Building;
use App\Models\Media;
use App\Models\Note;
use App\Models\Property;
use App\Models\PropertyRoom;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $this->get('/admin-locative')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_without_admin_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin-locative')
            ->assertForbidden();
    }

    public function test_admin_can_access_the_back_office(): void
    {
        $admin = $this->admin();
        $this->assertTrue($admin->can('access admin'));

        $this->actingAs($admin)
            ->get('/admin-locative')
            ->assertOk()
            ->assertSee('Vue d’ensemble');
    }

    public function test_admin_can_access_the_system_configuration_checklist(): void
    {
        $this->actingAs($this->admin())
            ->get(route('system-checks.index'))
            ->assertOk()
            ->assertSee('Configuration du serveur')
            ->assertSee('Stockage privé des pièces jointes')
            ->assertSee('Extensions PHP requises');

        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        $this->actingAs($manager)
            ->get(route('system-checks.index'))
            ->assertForbidden();
    }

    public function test_dashboard_displays_the_portfolio_summary_and_recent_items(): void
    {
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'DASH-APT-01',
            'name' => 'Appartement du tableau de bord',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);
        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MRS,
            'last_name' => 'Tableau',
            'first_name' => 'Jeanne',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin-locative')
            ->assertOk()
            ->assertSee('Points d’attention')
            ->assertSee('Immeubles récents')
            ->assertSee('Biens récents')
            ->assertSee('Locataires récents')
            ->assertSee($building->name)
            ->assertSee($property->name)
            ->assertSee($tenant->fullName())
            ->assertSee(route('buildings.create'))
            ->assertSee(route('properties.create'))
            ->assertSee(route('tenants.create'));
    }

    public function test_admin_can_create_a_building(): void
    {
        $this->actingAs($this->admin())
            ->post('/buildings', [
                'reference' => 'IMMEUBLE-01',
                'name' => 'Résidence des Tilleuls',
                'address' => [
                    'line1' => '10 rue des Tilleuls',
                    'postal_code' => '75001',
                    'city' => 'Paris',
                    'country' => 'FR',
                ],
            ])
            ->assertRedirect('/buildings');

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
            ->post('/properties', [
                'reference' => 'MAISON-01',
                'name' => 'Maison de campagne',
                'type' => 'house',
                'is_shared_accommodation' => '1',
                'status' => 'active',
                'address' => [
                    'line1' => '5 chemin du Lac',
                    'postal_code' => '33000',
                    'city' => 'Bordeaux',
                    'country' => 'FR',
                ],
            ])
            ->assertRedirect('/properties');

        $this->assertDatabaseHas('properties', [
            'reference' => 'MAISON-01',
            'type' => 'house',
            'building_id' => null,
            'is_shared_accommodation' => true,
        ]);
    }

    public function test_admin_can_mark_an_apartment_as_shared_accommodation(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->admin())
            ->post('/properties', [
                'reference' => 'COLOC-APP-01',
                'name' => 'Appartement en colocation',
                'type' => Property::TYPE_APARTMENT,
                'building_id' => $building->id,
                'is_shared_accommodation' => '1',
                'status' => 'active',
            ])
            ->assertRedirect('/properties');

        $this->assertDatabaseHas('properties', [
            'reference' => 'COLOC-APP-01',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'is_shared_accommodation' => true,
        ]);
    }

    public function test_parking_cannot_be_marked_as_shared_accommodation(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->admin())
            ->post('/properties', [
                'reference' => 'COLOC-PARKING-01',
                'name' => 'Parking impossible en colocation',
                'type' => Property::TYPE_PARKING,
                'building_id' => $building->id,
                'is_shared_accommodation' => '1',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('is_shared_accommodation');

        $this->assertDatabaseMissing('properties', [
            'reference' => 'COLOC-PARKING-01',
        ]);
    }

    public function test_apartment_requires_a_building(): void
    {
        $this->actingAs($this->admin())
            ->post('/properties', [
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
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee($property->reference);

        $this->actingAs($admin)
            ->put(route('properties.update', $property), [
                'reference' => 'APPART-MODIFIE',
                'name' => 'Appartement modifie',
                'type' => Property::TYPE_APARTMENT,
                'building_id' => $building->id,
                'status' => 'inactive',
            ])
            ->assertRedirect('/properties');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'reference' => 'APPART-MODIFIE',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete(route('properties.destroy', $property))
            ->assertRedirect('/properties');

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
            ->delete(route('buildings.destroy', $building))
            ->assertRedirect('/buildings')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('buildings', ['id' => $building->id]);
    }

    public function test_property_form_contains_dynamic_address_and_building_sections(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('properties.create'));

        $response->assertOk()
            ->assertSee('id="building-fields"', false)
            ->assertSee('id="shared-accommodation-field"', false)
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
            ->put(route('properties.update', $property), [
                'reference' => $property->reference,
                'name' => $property->name,
                'type' => Property::TYPE_APARTMENT,
                'building_id' => $building->id,
                'status' => 'active',
            ])
            ->assertRedirect('/properties');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'address_id' => null,
        ]);
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_admin_can_create_update_and_delete_a_room_on_a_shared_property(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'COLOC-ROOMS-01',
            'name' => 'Colocation test',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'is_shared_accommodation' => true,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('property-rooms.store', $property), [
                'name' => 'Chambre bleue',                'surface_m2' => 12.5,
                'status' => 'active',
                'notes' => 'Cote cour',
            ])
            ->assertRedirect(route('properties.show', $property));

        $room = PropertyRoom::firstOrFail();
        $this->assertDatabaseHas('property_rooms', [
            'id' => $room->id,
            'property_id' => $property->id,
            'name' => 'Chambre bleue',        ]);

        $this->actingAs($admin)
            ->get(route('property-rooms.show', [$property, $room]))
            ->assertOk()
            ->assertSee('Chambre bleue');

        $this->actingAs($admin)
            ->put(route('property-rooms.update', [$property, $room]), [
                'name' => 'Chambre verte',                'surface_m2' => 13,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('property-rooms.show', [$property, $room]));

        $this->assertDatabaseHas('property_rooms', [
            'id' => $room->id,
            'name' => 'Chambre verte',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete(route('property-rooms.destroy', [$property, $room]))
            ->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseMissing('property_rooms', ['id' => $room->id]);
    }

    public function test_room_cannot_be_created_on_a_non_shared_property(): void
    {
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'CLASSIC-ROOMS-01',
            'name' => 'Appartement classique',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'is_shared_accommodation' => false,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin())
            ->post(route('property-rooms.store', $property), [
                'name' => 'Chambre refusee',                'status' => 'active',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('property_rooms', [
            'name' => 'Chambre refusee',
        ]);
    }

    public function test_shared_accommodation_cannot_be_disabled_while_rooms_exist(): void
    {
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'COLOC-LOCKED-01',
            'name' => 'Colocation verrouillee',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'is_shared_accommodation' => true,
            'status' => 'active',
        ]);
        $property->rooms()->create([
            'name' => 'Chambre existante',            'status' => 'active',
        ]);

        $this->actingAs($this->admin())
            ->put(route('properties.update', $property), [
                'reference' => $property->reference,
                'name' => $property->name,
                'type' => Property::TYPE_APARTMENT,
                'building_id' => $building->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('is_shared_accommodation');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_shared_accommodation' => true,
        ]);
    }

    public function test_admin_has_no_media_access_on_a_property_room(): void
    {
        // Pièces de colocation : réservées à 'manage properties' (admin only), mais l'admin
        // n'a plus aucun accès aux médias, sur aucune entité (cf. docs/roles-permissions.md).
        // La gestion des médias de pièce est donc aujourd'hui hors d'atteinte pour tout rôle.
        $property = Property::create([
            'reference' => 'ROOM-MEDIA-01',
            'name' => 'Maison media pieces',
            'type' => Property::TYPE_HOUSE,
            'is_shared_accommodation' => true,
            'status' => 'active',
        ]);
        $room = $property->rooms()->create([
            'name' => 'Chambre photo',            'status' => 'active',
        ]);

        $this->actingAs($this->admin())
            ->post(route('property-rooms.media.store', [$property, $room]), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('chambre.jpg', 100, 'image/jpeg'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('media', [
            'mediable_type' => PropertyRoom::class,
            'mediable_id' => $room->id,
        ]);
    }

    public function test_admin_has_no_media_access_on_a_property(): void
    {
        $admin = $this->admin();
        $property = Property::create([
            'reference' => 'MEDIA-01',
            'name' => 'Bien media',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('properties.media.store', $property), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('facade.jpg', 100, 'image/jpeg'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('media', ['mediable_type' => Property::class, 'mediable_id' => $property->id]);
    }

    public function test_manager_can_manage_media_and_notes_only_on_a_property_they_manage(): void
    {
        $managedProperty = Property::create([
            'reference' => 'MEDIA-MANAGED-01',
            'name' => 'Bien géré',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);
        $otherProperty = Property::create([
            'reference' => 'MEDIA-OTHER-01',
            'name' => 'Bien non géré',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);

        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');
        $managedProperty->managers()->attach($manager);

        $this->actingAs($manager)
            ->post(route('properties.media.store', $managedProperty), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('facade.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('properties.show', $managedProperty));

        $photo = Media::firstOrFail();

        $this->actingAs($manager)
            ->delete(route('media.destroy', $photo))
            ->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $photo->id]);

        $this->actingAs($manager)
            ->post(route('properties.notes.store', $managedProperty), ['body' => 'Note autorisée'])
            ->assertRedirect(route('properties.show', $managedProperty));

        $this->assertDatabaseHas('notes', [
            'notable_type' => Property::class,
            'notable_id' => $managedProperty->id,
            'body' => 'Note autorisée',
        ]);

        $this->actingAs($manager)
            ->post(route('properties.media.store', $otherProperty), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('autre.jpg', 100, 'image/jpeg'),
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('properties.notes.store', $otherProperty), ['body' => 'Non autorisée'])
            ->assertForbidden();
    }

    public function test_media_upload_is_limited_to_twenty_megabytes_with_a_clear_error_message(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');
        $property = Property::create([
            'reference' => 'MEDIA-TOO-LARGE',
            'name' => 'Bien media volumineux',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);
        $property->managers()->attach($manager);

        $this->actingAs($manager)
            ->post(route('properties.media.store', $property), [
                'kind' => Media::KIND_DOCUMENT,
                'file' => UploadedFile::fake()->create('diagnostic.pdf', 20481, 'application/pdf'),
            ])
            ->assertSessionHasErrors([
                'file' => 'Le fichier sélectionné ne doit pas dépasser 20 Mo.',
            ]);
    }

    public function test_admin_can_create_update_and_delete_a_tenant(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('tenants.store'), [
                'civility' => Tenant::CIVILITY_MRS,
                'last_name' => 'Durand',
                'first_name' => 'Jeanne',
                'birth_date' => '1985-02-14',
                'status' => Tenant::STATUS_CANDIDATE,
            ])
            ->assertRedirect(route('tenants.index', ['status' => Tenant::STATUS_CANDIDATE]));

        $tenant = Tenant::firstOrFail();
        $this->assertSame('Jeanne Durand', $tenant->fullName());
        $this->assertSame(Tenant::STATUS_CANDIDATE, $tenant->status);

        $this->actingAs($admin)
            ->put(route('tenants.update', $tenant), [
                'civility' => Tenant::CIVILITY_MRS,
                'last_name' => 'Durand',
                'first_name' => 'Jeanne',
                'birth_date' => '1985-02-14',
                'status' => Tenant::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('tenants.index', ['status' => Tenant::STATUS_ACTIVE]));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->delete(route('tenants.destroy', $tenant))
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_tenant_list_displays_active_tenants_by_default_and_filters_by_status(): void
    {
        $activeTenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Actif',
            'first_name' => 'Alain',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $candidateTenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MRS,
            'last_name' => 'Candidate',
            'first_name' => 'Claire',
            'status' => Tenant::STATUS_CANDIDATE,
        ]);
        Tenant::create([
            'civility' => Tenant::CIVILITY_OTHER,
            'last_name' => 'Refuse',
            'first_name' => 'Rene',
            'status' => Tenant::STATUS_REFUSED,
        ]);

        $this->actingAs($this->admin())
            ->get(route('tenants.index'))
            ->assertOk()
            ->assertSee($activeTenant->fullName())
            ->assertDontSee($candidateTenant->fullName());

        $this->actingAs($this->admin())
            ->get(route('tenants.index', ['status' => Tenant::STATUS_CANDIDATE]))
            ->assertOk()
            ->assertSee($candidateTenant->fullName())
            ->assertDontSee($activeTenant->fullName());
    }

    public function test_admin_can_manage_tenant_media_and_manager_is_locked_out_of_admin_tenant_pages(): void
    {
        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Martin',
            'first_name' => 'Paul',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $admin = $this->admin();

        // Tenant media is unreachable for admin (no media access anywhere) and for a
        // manager (no 'manage tenants' permission any more): the feature has no valid
        // actor today.
        $this->actingAs($admin)
            ->post(route('tenants.media.store', $tenant), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('portrait.jpg', 100, 'image/jpeg'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('media', ['mediable_type' => Tenant::class, 'mediable_id' => $tenant->id]);

        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        // /tenants is now admin-only: the manager cannot reach it at all, read or write,
        // even by direct URL. They would use /mes-locataires instead (see RoleScopingTest),
        // and this tenant is not attached to any of their managed properties anyway.
        $this->actingAs($manager)
            ->get(route('tenants.show', $tenant))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('tenants.edit', $tenant))
            ->assertForbidden();

        $this->actingAs($manager)
            ->put(route('tenants.update', $tenant), [
                'civility' => Tenant::CIVILITY_MR,
                'last_name' => 'Martin',
                'first_name' => 'Paul',
                'status' => Tenant::STATUS_ACTIVE,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('tenants.destroy', $tenant))
            ->assertForbidden();

        // Creating a tenant is now admin-only: even a manager who manages a property
        // cannot create one any more (route locked with role:admin).
        $building = $this->createBuilding();
        $managedProperty = Property::create([
            'reference' => 'TENANT-SCOPE-01',
            'name' => 'Bien géré du gestionnaire',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);
        $managedProperty->managers()->attach($manager);

        $this->actingAs($manager)
            ->post(route('tenants.store'), [
                'civility' => Tenant::CIVILITY_MRS,
                'last_name' => 'Gestionnaire',
                'first_name' => 'Sophie',
                'status' => Tenant::STATUS_CANDIDATE,
                'properties' => [$managedProperty->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('tenants', ['first_name' => 'Sophie']);
    }

    public function test_admin_has_no_note_access_on_a_building(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();

        $this->actingAs($admin)
            ->post(route('buildings.notes.store', $building), [
                'body' => 'Interphone à changer',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('notes', 0);

        $this->actingAs($admin)
            ->get(route('buildings.show', $building))
            ->assertOk()
            ->assertDontSee('Fil de notes');
    }

    public function test_manager_can_never_add_notes_on_buildings_or_tenants_even_on_a_managed_property(): void
    {
        // Adding a note on a building or a tenant is now admin-only: the manager's
        // dedicated pages (/mes-immeubles, /mes-locataires) are read-only, including
        // for notes. This is a deliberate change from an earlier iteration where the
        // manager could add such notes when they managed the related property.
        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'NOTE-SCOPE-01',
            'name' => 'Bien géré',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);

        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Locataire',
            'first_name' => 'Test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->post(route('buildings.notes.store', $building), ['body' => 'Non autorisé'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tenants.notes.store', $tenant), ['body' => 'Non autorisé'])
            ->assertForbidden();

        $this->assertDatabaseCount('notes', 0);

        // Still forbidden even once the manager manages the building's property and the
        // tenant is attached to it.
        $property->managers()->attach($manager);
        $tenant->properties()->attach($property);

        $this->actingAs($manager)
            ->post(route('buildings.notes.store', $building), ['body' => 'Toujours non autorisé'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tenants.notes.store', $tenant), ['body' => 'Toujours non autorisé'])
            ->assertForbidden();

        $this->assertDatabaseCount('notes', 0);

        // Admin has no note access either, on any entity.
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('buildings.notes.store', $building), ['body' => 'Toujours non autorisé pour admin'])
            ->assertForbidden();

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_only_the_author_can_edit_or_delete_a_note(): void
    {
        $author = User::factory()->create();
        $author->assignRole('gestionnaire');
        $colleague = User::factory()->create();
        $colleague->assignRole('gestionnaire');
        $admin = $this->admin();

        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'NOTE-AUTHOR-01',
            'name' => 'Bien géré par l’auteur',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);
        $property->managers()->attach($author);

        $this->actingAs($author)
            ->post(route('properties.notes.store', $property), ['body' => 'Note initiale'])
            ->assertRedirect();

        $note = Note::firstOrFail();

        $this->actingAs($colleague)
            ->put(route('notes.update', $note), ['body' => 'Tentative non autorisée'])
            ->assertForbidden();

        $this->actingAs($colleague)
            ->delete(route('notes.destroy', $note))
            ->assertForbidden();

        $this->actingAs($author)
            ->put(route('notes.update', $note), ['body' => 'Modifiée par l’auteur'])
            ->assertRedirect();

        $note->refresh();
        $this->assertSame('Modifiée par l’auteur', $note->body);

        // Admin no longer has any note access, including moderating someone else's note.
        $this->actingAs($admin)
            ->delete(route('notes.destroy', $note))
            ->assertForbidden();

        $this->actingAs($author)
            ->delete(route('notes.destroy', $note))
            ->assertRedirect();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_notes_are_available_on_properties_and_property_rooms_for_their_manager(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'NOTE-PROP-01',
            'name' => 'Bien avec notes',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'is_shared_accommodation' => true,
            'status' => 'active',
        ]);
        $property->managers()->attach($manager);
        $room = $property->rooms()->create([
            'name' => 'Chambre notée',
            'status' => 'active',
        ]);

        $this->actingAs($manager)
            ->post(route('properties.notes.store', $property), ['body' => 'Note bien'])
            ->assertRedirect(route('properties.show', $property));

        $this->actingAs($manager)
            ->post(route('property-rooms.notes.store', [$property, $room]), ['body' => 'Note pièce'])
            ->assertRedirect(route('property-rooms.show', [$property, $room]));

        $this->assertDatabaseHas('notes', [
            'notable_type' => Property::class,
            'notable_id' => $property->id,
            'body' => 'Note bien',
        ]);
        $this->assertDatabaseHas('notes', [
            'notable_type' => PropertyRoom::class,
            'notable_id' => $room->id,
            'body' => 'Note pièce',
        ]);

        $this->actingAs($manager)
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Note bien');

        $this->actingAs($manager)
            ->get(route('property-rooms.show', [$property, $room]))
            ->assertOk()
            ->assertSee('Note pièce');

        // Admin has no note access at all, including viewing existing notes.
        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertDontSee('Fil de notes');
    }

    public function test_tenant_can_be_linked_to_a_future_user_account(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MRS,
            'last_name' => 'Profil',
            'first_name' => 'Louise',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $tenant->user()->associate($user);
        $tenant->save();

        $this->assertTrue($tenant->user->is($user));
        $this->assertTrue($user->tenant->is($tenant));
    }

    public function test_admin_can_put_a_property_in_management_for_a_manager(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'GESTION-01',
            'name' => 'Bien en gestion',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);
        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        $this->actingAs($admin)
            ->put(route('properties.managers.update', $property), [
                'managers' => [$manager->id],
            ])
            ->assertRedirect();

        $this->assertTrue($property->managers()->whereKey($manager->id)->exists());
        $this->assertTrue($manager->managedProperties()->whereKey($property->id)->exists());
    }

    public function test_manager_only_sees_properties_put_in_management_for_them(): void
    {
        $building = $this->createBuilding();
        $ownProperty = Property::create([
            'reference' => 'GESTION-02',
            'name' => 'Bien géré',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);
        $otherProperty = Property::create([
            'reference' => 'GESTION-03',
            'name' => 'Bien non géré',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'status' => 'active',
        ]);

        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');
        $ownProperty->managers()->attach($manager);

        $this->actingAs($manager)
            ->get(route('mes-biens'))
            ->assertOk()
            ->assertSee($ownProperty->name)
            ->assertDontSee($otherProperty->name);

        $this->actingAs($manager)
            ->get(route('mes-biens.show', $ownProperty))
            ->assertOk()
            ->assertSee($ownProperty->name)
            ->assertDontSee('Modifier')
            ->assertDontSee('Mise en gestion');

        $this->actingAs($manager)
            ->get(route('mes-biens.show', $otherProperty))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('mes-biens.show', $ownProperty))
            ->assertSee('Ajouter une pièce jointe')
            ->assertSee('Ajouter une note');
    }

    public function test_locataire_can_only_reach_mes_contrats(): void
    {
        $locataire = User::factory()->create();
        $locataire->assignRole('locataire');

        $this->actingAs($locataire)
            ->get(route('gestion-locative'))
            ->assertOk()
            ->assertDontSee('Mes biens')
            ->assertDontSee('Mes locataires');

        $this->actingAs($locataire)
            ->get(route('mes-contrats'))
            ->assertOk();

        $this->actingAs($locataire)
            ->get(route('mes-biens'))
            ->assertForbidden();

        $this->actingAs($locataire)
            ->get(route('mes-locataires'))
            ->assertForbidden();

        $this->actingAs($locataire)
            ->get(route('admin-locative'))
            ->assertForbidden();
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
