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

    public function test_admin_can_access_the_system_configuration_checklist(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system-checks.index'))
            ->assertOk()
            ->assertSee('Configuration du serveur')
            ->assertSee('Stockage privé des pièces jointes')
            ->assertSee('Extensions PHP requises');

        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        $this->actingAs($manager)
            ->get(route('admin.system-checks.index'))
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
            ->get('/admin')
            ->assertOk()
            ->assertSee('Points d’attention')
            ->assertSee('Immeubles récents')
            ->assertSee('Biens récents')
            ->assertSee('Locataires récents')
            ->assertSee($building->name)
            ->assertSee($property->name)
            ->assertSee($tenant->fullName())
            ->assertSee(route('admin.buildings.create'))
            ->assertSee(route('admin.properties.create'))
            ->assertSee(route('admin.tenants.create'));
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
                'is_shared_accommodation' => '1',
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
            'is_shared_accommodation' => true,
        ]);
    }

    public function test_admin_can_mark_an_apartment_as_shared_accommodation(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->admin())
            ->post('/admin/properties', [
                'reference' => 'COLOC-APP-01',
                'name' => 'Appartement en colocation',
                'type' => Property::TYPE_APARTMENT,
                'building_id' => $building->id,
                'is_shared_accommodation' => '1',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/properties');

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
            ->post('/admin/properties', [
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
            ->post(route('admin.property-rooms.store', $property), [
                'name' => 'Chambre bleue',                'surface_m2' => 12.5,
                'status' => 'active',
                'notes' => 'Cote cour',
            ])
            ->assertRedirect(route('admin.properties.show', $property));

        $room = PropertyRoom::firstOrFail();
        $this->assertDatabaseHas('property_rooms', [
            'id' => $room->id,
            'property_id' => $property->id,
            'name' => 'Chambre bleue',        ]);

        $this->actingAs($admin)
            ->get(route('admin.property-rooms.show', [$property, $room]))
            ->assertOk()
            ->assertSee('Chambre bleue');

        $this->actingAs($admin)
            ->put(route('admin.property-rooms.update', [$property, $room]), [
                'name' => 'Chambre verte',                'surface_m2' => 13,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.property-rooms.show', [$property, $room]));

        $this->assertDatabaseHas('property_rooms', [
            'id' => $room->id,
            'name' => 'Chambre verte',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.property-rooms.destroy', [$property, $room]))
            ->assertRedirect(route('admin.properties.show', $property));

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
            ->post(route('admin.property-rooms.store', $property), [
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
            ->put(route('admin.properties.update', $property), [
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

    public function test_admin_can_upload_media_on_a_property_room(): void
    {
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
            ->post(route('admin.property-rooms.media.store', [$property, $room]), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('chambre.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.property-rooms.show', [$property, $room]));

        $this->assertDatabaseHas('media', [
            'mediable_type' => PropertyRoom::class,
            'mediable_id' => $room->id,
            'kind' => Media::KIND_PHOTO,
            'is_primary' => true,
        ]);
    }

    public function test_admin_can_upload_tag_and_manage_media_on_a_property(): void
    {
        $admin = $this->admin();
        $property = Property::create([
            'reference' => 'MEDIA-01',
            'name' => 'Bien media',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.properties.media.store', $property), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('facade.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.properties.show', $property));

        $photo = Media::firstOrFail();
        $this->assertTrue($photo->is_primary);

        $this->actingAs($admin)
            ->post(route('admin.properties.media.store', $property), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('salon.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.properties.show', $property));

        $secondPhoto = Media::where('kind', Media::KIND_PHOTO)->where('id', '!=', $photo->id)->firstOrFail();
        $this->assertSame(1, Media::where('kind', Media::KIND_PHOTO)->where('is_primary', true)->count());

        $this->actingAs($admin)
            ->post(route('admin.properties.media.store', $property), [
                'kind' => Media::KIND_DOCUMENT,
                'type' => Media::TYPE_DIAGNOSTICS,
                'file' => UploadedFile::fake()->create('diagnostic.pdf', 100, 'application/pdf'),
                'display_name' => 'Diagnostic énergétique',
                'tags' => 'diagnostic, énergie',
            ])
            ->assertRedirect(route('admin.properties.show', $property));

        $document = Media::where('kind', Media::KIND_DOCUMENT)->firstOrFail();
        $this->assertSame(Media::TYPE_DIAGNOSTICS, $document->type);
        $this->assertStringContainsString('media/property/MEDIA-01/diagnostics/', $document->path);
        $this->assertSame('Diagnostic énergétique', $document->display_name);
        $this->assertDatabaseHas('tags', ['name' => 'diagnostic']);
        $this->assertDatabaseHas('tags', ['name' => 'énergie']);

        $this->actingAs($admin)
            ->get(route('admin.media.download', $document))
            ->assertOk();

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $photo))
            ->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $photo->id]);
        $this->assertDatabaseHas('media', ['id' => $secondPhoto->id, 'is_primary' => true]);
    }

    public function test_media_upload_is_limited_to_twenty_megabytes_with_a_clear_error_message(): void
    {
        $admin = $this->admin();
        $property = Property::create([
            'reference' => 'MEDIA-TOO-LARGE',
            'name' => 'Bien media volumineux',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.properties.media.store', $property), [
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
            ->post(route('admin.tenants.store'), [
                'civility' => Tenant::CIVILITY_MRS,
                'last_name' => 'Durand',
                'first_name' => 'Jeanne',
                'birth_date' => '1985-02-14',
                'status' => Tenant::STATUS_CANDIDATE,
            ])
            ->assertRedirect(route('admin.tenants.index', ['status' => Tenant::STATUS_CANDIDATE]));

        $tenant = Tenant::firstOrFail();
        $this->assertSame('Jeanne Durand', $tenant->fullName());
        $this->assertSame(Tenant::STATUS_CANDIDATE, $tenant->status);

        $this->actingAs($admin)
            ->put(route('admin.tenants.update', $tenant), [
                'civility' => Tenant::CIVILITY_MRS,
                'last_name' => 'Durand',
                'first_name' => 'Jeanne',
                'birth_date' => '1985-02-14',
                'status' => Tenant::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.tenants.index', ['status' => Tenant::STATUS_ACTIVE]));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.tenants.destroy', $tenant))
            ->assertRedirect(route('admin.tenants.index'));

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
            ->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee($activeTenant->fullName())
            ->assertDontSee($candidateTenant->fullName());

        $this->actingAs($this->admin())
            ->get(route('admin.tenants.index', ['status' => Tenant::STATUS_CANDIDATE]))
            ->assertOk()
            ->assertSee($candidateTenant->fullName())
            ->assertDontSee($activeTenant->fullName());
    }

    public function test_admin_can_manage_tenant_media_and_manager_can_manage_tenants(): void
    {
        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Martin',
            'first_name' => 'Paul',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.tenants.media.store', $tenant), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('portrait.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $photo = Media::firstOrFail();
        $this->assertTrue($photo->is_primary);

        $this->actingAs($admin)
            ->post(route('admin.tenants.media.store', $tenant), [
                'kind' => Media::KIND_PHOTO,
                'file' => UploadedFile::fake()->create('second-portrait.jpg', 100, 'image/jpeg'),
            ])
            ->assertSessionHasErrors([
                'file' => 'Un locataire ne peut avoir qu’une seule photo d’identité. Supprimez-la avant d’en ajouter une autre.',
            ]);

        $this->assertSame(1, $tenant->media()->where('kind', Media::KIND_PHOTO)->count());

        $this->actingAs($admin)
            ->get(route('admin.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('detail-hero-photo', false)
            ->assertSee('Photo d’identité');

        $this->actingAs($admin)
            ->post(route('admin.tenants.media.store', $tenant), [
                'kind' => Media::KIND_DOCUMENT,
                'type' => Media::TYPE_IDENTITY,
                'file' => UploadedFile::fake()->create('dossier.pdf', 100, 'application/pdf'),
                'display_name' => 'Dossier de candidature',
                'tags' => 'candidature, identité',
            ])
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseHas('media', [
            'mediable_type' => Tenant::class,
            'mediable_id' => $tenant->id,
            'type' => Media::TYPE_IDENTITY,
            'display_name' => 'Dossier de candidature',
        ]);
        $this->assertStringContainsString('media/tenant/'.$tenant->storage_key.'/identity/', Media::where('mediable_id', $tenant->id)->where('type', Media::TYPE_IDENTITY)->value('path'));

        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        $this->actingAs($manager)
            ->get(route('admin.tenants.show', $tenant))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('admin.tenants.create'))
            ->assertOk();

        $this->actingAs($manager)
            ->post(route('admin.tenants.store'), [
                'civility' => Tenant::CIVILITY_MRS,
                'last_name' => 'Gestionnaire',
                'first_name' => 'Sophie',
                'status' => Tenant::STATUS_CANDIDATE,
            ])
            ->assertRedirect(route('admin.tenants.index', ['status' => Tenant::STATUS_CANDIDATE]));
    }

    public function test_admin_can_add_edit_and_delete_a_note_on_a_building(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();

        $this->actingAs($admin)
            ->post(route('admin.buildings.notes.store', $building), [
                'body' => 'Interphone à changer',
            ])
            ->assertRedirect(route('admin.buildings.show', $building));

        $note = Note::firstOrFail();
        $this->assertSame(Building::class, $note->notable_type);
        $this->assertSame($building->id, $note->notable_id);
        $this->assertSame($admin->id, $note->created_by);
        $this->assertNull($note->updated_by);
        $this->assertFalse($note->wasEdited());

        $this->actingAs($admin)
            ->get(route('admin.buildings.show', $building))
            ->assertOk()
            ->assertSee('Interphone à changer')
            ->assertSee($admin->name);

        $this->actingAs($admin)
            ->put(route('admin.notes.update', $note), [
                'body' => 'Interphone changé',
            ])
            ->assertRedirect();

        $note->refresh();
        $this->assertSame('Interphone changé', $note->body);
        $this->assertSame($admin->id, $note->updated_by);
        $this->assertTrue($note->wasEdited());

        $this->actingAs($admin)
            ->delete(route('admin.notes.destroy', $note))
            ->assertRedirect();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_note_add_permission_matches_manage_permission_on_the_parent(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        $building = $this->createBuilding();

        $this->actingAs($manager)
            ->post(route('admin.buildings.notes.store', $building), ['body' => 'Non autorisé'])
            ->assertForbidden();

        $this->assertDatabaseCount('notes', 0);

        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Locataire',
            'first_name' => 'Test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->post(route('admin.tenants.notes.store', $tenant), ['body' => 'Autorisé'])
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseCount('notes', 1);
    }

    public function test_only_the_author_or_admin_can_edit_or_delete_a_note(): void
    {
        $author = User::factory()->create();
        $author->assignRole('gestionnaire');
        $colleague = User::factory()->create();
        $colleague->assignRole('gestionnaire');
        $admin = $this->admin();

        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Locataire',
            'first_name' => 'Test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->actingAs($author)
            ->post(route('admin.tenants.notes.store', $tenant), ['body' => 'Note initiale'])
            ->assertRedirect();

        $note = Note::firstOrFail();

        $this->actingAs($colleague)
            ->put(route('admin.notes.update', $note), ['body' => 'Tentative non autorisée'])
            ->assertForbidden();

        $this->actingAs($colleague)
            ->delete(route('admin.notes.destroy', $note))
            ->assertForbidden();

        $this->actingAs($author)
            ->put(route('admin.notes.update', $note), ['body' => 'Modifiée par l’auteur'])
            ->assertRedirect();

        $note->refresh();
        $this->assertSame('Modifiée par l’auteur', $note->body);

        $this->actingAs($admin)
            ->delete(route('admin.notes.destroy', $note))
            ->assertRedirect();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_notes_are_available_on_properties_and_property_rooms(): void
    {
        $admin = $this->admin();
        $building = $this->createBuilding();
        $property = Property::create([
            'reference' => 'NOTE-PROP-01',
            'name' => 'Bien avec notes',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'is_shared_accommodation' => true,
            'status' => 'active',
        ]);
        $room = $property->rooms()->create([
            'name' => 'Chambre notée',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.properties.notes.store', $property), ['body' => 'Note bien'])
            ->assertRedirect(route('admin.properties.show', $property));

        $this->actingAs($admin)
            ->post(route('admin.property-rooms.notes.store', [$property, $room]), ['body' => 'Note pièce'])
            ->assertRedirect(route('admin.property-rooms.show', [$property, $room]));

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

        $this->actingAs($admin)
            ->get(route('admin.properties.show', $property))
            ->assertOk()
            ->assertSee('Note bien');

        $this->actingAs($admin)
            ->get(route('admin.property-rooms.show', [$property, $room]))
            ->assertOk()
            ->assertSee('Note pièce');
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
