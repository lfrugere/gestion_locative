<?php

namespace Tests\Unit\Models;

use App\Models\Building;
use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyRoom;
use App\Models\Tenant;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    public function test_is_photo_distinguishes_kind_photo_from_kind_document(): void
    {
        $photo = new Media(['kind' => Media::KIND_PHOTO]);
        $document = new Media(['kind' => Media::KIND_DOCUMENT]);

        $this->assertTrue($photo->isPhoto());
        $this->assertFalse($document->isPhoto());
    }

    public function test_type_label_returns_the_known_french_label(): void
    {
        $media = new Media(['type' => Media::TYPE_BANK_DETAILS]);

        $this->assertSame('RIB', $media->typeLabel());
    }

    public function test_type_label_falls_back_to_the_raw_value_for_an_unknown_type(): void
    {
        $media = new Media(['type' => 'other-custom']);

        $this->assertSame('other-custom', $media->typeLabel());
    }

    public function test_types_for_a_tenant_include_identity_and_bank_details_but_no_diagnostics(): void
    {
        $types = Media::typesFor(new Tenant());

        $this->assertSame([
            Media::TYPE_PHOTOS,
            Media::TYPE_IDENTITY,
            Media::TYPE_BANK_DETAILS,
            Media::TYPE_INSURANCE,
            Media::TYPE_OTHER,
        ], $types);
    }

    public function test_types_for_a_building_and_a_property_include_diagnostics_but_no_identity(): void
    {
        $expected = [
            Media::TYPE_PHOTOS,
            Media::TYPE_DIAGNOSTICS,
            Media::TYPE_INSURANCE,
            Media::TYPE_OTHER,
        ];

        $this->assertSame($expected, Media::typesFor(new Building()));
        $this->assertSame($expected, Media::typesFor(new Property()));
    }

    public function test_types_for_a_property_room_match_a_building_and_a_property(): void
    {
        $this->assertSame(Media::typesFor(new Building()), Media::typesFor(new PropertyRoom()));
    }

    public function test_document_types_for_exclude_photos(): void
    {
        $this->assertNotContains(Media::TYPE_PHOTOS, Media::documentTypesFor(new Tenant()));
        $this->assertNotContains(Media::TYPE_PHOTOS, Media::documentTypesFor(new Building()));
        $this->assertNotContains(Media::TYPE_PHOTOS, Media::documentTypesFor(new PropertyRoom()));
    }
}
