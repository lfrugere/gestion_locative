<?php

namespace Tests\Unit\Services;

use App\Models\Address;
use App\Models\Building;
use App\Models\Media;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\MediaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    private MediaManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->manager = new MediaManager();
    }

    public function test_store_places_a_photo_under_the_owner_photos_directory(): void
    {
        $building = $this->createBuilding();

        $media = $this->manager->store(
            $building,
            UploadedFile::fake()->create('facade.jpg', 100, 'image/jpeg'),
            Media::KIND_PHOTO,
            Media::TYPE_PHOTOS,
            null,
            null,
        );

        $this->assertStringStartsWith("media/building/{$building->reference}/photos/", $media->path);
        Storage::disk('local')->assertExists($media->path);
    }

    public function test_store_places_a_photo_under_the_tenant_storage_key_directory(): void
    {
        $tenant = $this->createTenant();

        $media = $this->manager->store(
            $tenant,
            UploadedFile::fake()->create('identity.jpg', 100, 'image/jpeg'),
            Media::KIND_PHOTO,
            Media::TYPE_PHOTOS,
            null,
            null,
        );

        $this->assertStringStartsWith("media/tenant/{$tenant->storage_key}/photos/", $media->path);
    }

    public function test_store_places_a_document_under_its_own_type_directory(): void
    {
        $property = $this->createProperty();

        $media = $this->manager->store(
            $property,
            UploadedFile::fake()->create('diagnostic.pdf', 100, 'application/pdf'),
            Media::KIND_DOCUMENT,
            Media::TYPE_DIAGNOSTICS,
            null,
            null,
        );

        $this->assertStringStartsWith("media/property/{$property->reference}/diagnostics/", $media->path);
    }

    public function test_store_marks_only_the_first_photo_as_primary(): void
    {
        $building = $this->createBuilding();

        $first = $this->manager->store($building, UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);
        $second = $this->manager->store($building, UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);

        $this->assertTrue($first->fresh()->is_primary);
        $this->assertFalse($second->fresh()->is_primary);
    }

    public function test_store_throws_for_an_unsupported_mediable_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager->store(
            new \stdClass(),
            UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
            Media::KIND_PHOTO,
            Media::TYPE_PHOTOS,
            null,
            null,
        );
    }

    public function test_update_moves_the_file_when_the_type_changes(): void
    {
        $property = $this->createProperty();
        $media = $this->manager->store(
            $property,
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            Media::KIND_DOCUMENT,
            Media::TYPE_DIAGNOSTICS,
            null,
            null,
        );
        $originalPath = $media->path;

        $this->manager->update($media, Media::TYPE_INSURANCE, null, null);

        $media->refresh();
        Storage::disk('local')->assertMissing($originalPath);
        Storage::disk('local')->assertExists($media->path);
        $this->assertStringStartsWith("media/property/{$property->reference}/insurance/", $media->path);
        $this->assertSame(Media::TYPE_INSURANCE, $media->type);
    }

    public function test_update_does_not_move_the_file_when_the_type_is_unchanged(): void
    {
        $property = $this->createProperty();
        $media = $this->manager->store(
            $property,
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            Media::KIND_DOCUMENT,
            Media::TYPE_DIAGNOSTICS,
            null,
            null,
        );
        $originalPath = $media->path;

        $this->manager->update($media, Media::TYPE_DIAGNOSTICS, null, null);

        $this->assertSame($originalPath, $media->fresh()->path);
        Storage::disk('local')->assertExists($originalPath);
    }

    public function test_update_syncs_tags_trimming_deduplicating_and_truncating(): void
    {
        $property = $this->createProperty();
        $media = $this->manager->store(
            $property,
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            Media::KIND_DOCUMENT,
            Media::TYPE_DIAGNOSTICS,
            null,
            null,
        );

        $this->manager->update($media, Media::TYPE_DIAGNOSTICS, null, ' bail , bail ,travaux ,  ');

        $this->assertSame(['bail', 'travaux'], $media->tags()->pluck('name')->sort()->values()->all());
    }

    public function test_set_primary_moves_the_primary_flag_to_the_chosen_photo(): void
    {
        $building = $this->createBuilding();
        $first = $this->manager->store($building, UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);
        $second = $this->manager->store($building, UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);

        $this->manager->setPrimary($second->fresh());

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_delete_removes_the_file_and_promotes_the_oldest_remaining_photo_to_primary(): void
    {
        $building = $this->createBuilding();
        $primary = $this->manager->store($building, UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);
        $other = $this->manager->store($building, UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);
        $path = $primary->path;

        $this->manager->delete($primary->fresh());

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('media', ['id' => $primary->id]);
        $this->assertTrue($other->fresh()->is_primary);
    }

    public function test_delete_of_a_non_primary_photo_leaves_the_primary_flag_untouched(): void
    {
        $building = $this->createBuilding();
        $primary = $this->manager->store($building, UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);
        $other = $this->manager->store($building, UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'), Media::KIND_PHOTO, Media::TYPE_PHOTOS, null, null);

        $this->manager->delete($other->fresh());

        $this->assertDatabaseMissing('media', ['id' => $other->id]);
        $this->assertTrue($primary->fresh()->is_primary);
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

    private function createProperty(): Property
    {
        return Property::create([
            'reference' => 'BIEN-TEST',
            'name' => 'Bien test',
            'type' => Property::TYPE_HOUSE,
            'status' => 'active',
        ]);
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'status' => Tenant::STATUS_CANDIDATE,
        ]);
    }
}
