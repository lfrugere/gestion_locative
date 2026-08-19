<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Building;
use App\Models\Media;
use App\Models\Property;
use App\Models\User;
use App\Services\MediaManager;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionRegistrar::class)->registerPermissions(app(Gate::class));
        Storage::fake('local');
    }

    public function test_updating_a_media_type_via_http_moves_the_file(): void
    {
        $property = $this->createProperty();
        $media = $this->createDocument($property, Media::TYPE_DIAGNOSTICS);
        $originalPath = $media->path;

        $this->actingAs($this->admin())
            ->put(route('media.update', $media), [
                'type' => Media::TYPE_INSURANCE,
                'display_name' => 'Attestation assurance',
            ])
            ->assertRedirect();

        $media->refresh();
        Storage::disk('local')->assertMissing($originalPath);
        Storage::disk('local')->assertExists($media->path);
        $this->assertSame(Media::TYPE_INSURANCE, $media->type);
        $this->assertSame('Attestation assurance', $media->display_name);
    }

    public function test_update_rejects_a_type_not_allowed_for_the_owner(): void
    {
        $building = $this->createBuilding();
        $media = $this->createDocument($building, Media::TYPE_DIAGNOSTICS);

        $this->actingAs($this->admin())
            ->put(route('media.update', $media), [
                'type' => Media::TYPE_IDENTITY,
                'display_name' => 'Pièce identité',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_update_is_forbidden_for_a_manager_without_manage_permission_on_the_owner(): void
    {
        $building = $this->createBuilding();
        $media = $this->createDocument($building, Media::TYPE_DIAGNOSTICS);

        $this->actingAs($this->manager())
            ->put(route('media.update', $media), [
                'type' => Media::TYPE_INSURANCE,
                'display_name' => 'Attestation',
            ])
            ->assertForbidden();
    }

    public function test_set_primary_marks_a_photo_as_primary(): void
    {
        $building = $this->createBuilding();
        $this->createPhoto($building);
        $second = $this->createPhoto($building);

        $this->actingAs($this->admin())
            ->post(route('media.primary', $second))
            ->assertRedirect();

        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(1, Media::where('kind', Media::KIND_PHOTO)->where('is_primary', true)->count());
    }

    public function test_set_primary_is_forbidden_for_a_non_photo_media(): void
    {
        $property = $this->createProperty();
        $document = $this->createDocument($property, Media::TYPE_DIAGNOSTICS);

        $this->actingAs($this->admin())
            ->post(route('media.primary', $document))
            ->assertForbidden();
    }

    public function test_set_primary_is_forbidden_without_manage_permission_on_the_owner(): void
    {
        $building = $this->createBuilding();
        $photo = $this->createPhoto($building);

        $this->actingAs($this->manager())
            ->post(route('media.primary', $photo))
            ->assertForbidden();
    }

    public function test_destroy_promotes_another_photo_as_primary_via_http(): void
    {
        $building = $this->createBuilding();
        $primary = $this->createPhoto($building);
        $other = $this->createPhoto($building);

        $this->actingAs($this->admin())
            ->delete(route('media.destroy', $primary))
            ->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $primary->id]);
        $this->assertTrue($other->fresh()->is_primary);
    }

    public function test_destroy_is_forbidden_without_manage_permission_on_the_owner(): void
    {
        $building = $this->createBuilding();
        $photo = $this->createPhoto($building);

        $this->actingAs($this->manager())
            ->delete(route('media.destroy', $photo))
            ->assertForbidden();

        $this->assertDatabaseHas('media', ['id' => $photo->id]);
    }

    public function test_download_redirects_guests_to_login(): void
    {
        $building = $this->createBuilding();
        $media = $this->createPhoto($building);

        $this->get(route('media.download', $media))
            ->assertRedirect(route('login'));
    }

    public function test_download_is_forbidden_without_the_view_permission_on_the_owner(): void
    {
        $building = $this->createBuilding();
        $media = $this->createPhoto($building);

        $user = User::factory()->create();
        $user->givePermissionTo('access admin');

        $this->actingAs($user)
            ->get(route('media.download', $media))
            ->assertForbidden();
    }

    public function test_download_is_allowed_for_a_manager_with_view_permission_even_without_manage_permission(): void
    {
        $building = $this->createBuilding();
        $media = $this->createPhoto($building);

        $this->actingAs($this->manager())
            ->get(route('media.download', $media))
            ->assertOk();
    }

    public function test_store_rejects_an_invalid_kind(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->admin())
            ->post(route('buildings.media.store', $building), [
                'kind' => 'video',
                'file' => UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('kind');
    }

    public function test_store_rejects_a_file_with_a_disallowed_mime_type(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->admin())
            ->post(route('buildings.media.store', $building), [
                'kind' => Media::KIND_DOCUMENT,
                'type' => Media::TYPE_OTHER,
                'file' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('file');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire');

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
            'reference' => 'IMMEUBLE-MEDIA',
            'name' => 'Immeuble media',
            'address_id' => $address->id,
        ]);
    }

    private function createProperty(): Property
    {
        return Property::create([
            'reference' => 'BIEN-MEDIA',
            'name' => 'Bien media',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);
    }

    private function createPhoto(object $mediable): Media
    {
        return app(MediaManager::class)->store(
            $mediable,
            UploadedFile::fake()->create('photo-'.uniqid().'.jpg', 100, 'image/jpeg'),
            Media::KIND_PHOTO,
            Media::TYPE_PHOTOS,
            null,
            null,
        );
    }

    private function createDocument(object $mediable, string $type): Media
    {
        return app(MediaManager::class)->store(
            $mediable,
            UploadedFile::fake()->create('doc-'.uniqid().'.pdf', 100, 'application/pdf'),
            Media::KIND_DOCUMENT,
            $type,
            null,
            null,
        );
    }
}
