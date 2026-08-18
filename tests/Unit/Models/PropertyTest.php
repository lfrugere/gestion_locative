<?php

namespace Tests\Unit\Models;

use App\Models\Property;
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    public function test_apartments_and_houses_can_be_shared_accommodation(): void
    {
        $this->assertTrue((new Property(['type' => Property::TYPE_APARTMENT]))->canBeSharedAccommodation());
        $this->assertTrue((new Property(['type' => Property::TYPE_HOUSE]))->canBeSharedAccommodation());
    }

    public function test_parkings_cannot_be_shared_accommodation(): void
    {
        $this->assertFalse((new Property(['type' => Property::TYPE_PARKING]))->canBeSharedAccommodation());
    }

    public function test_can_have_rooms_requires_both_the_shared_flag_and_an_eligible_type(): void
    {
        $sharedApartment = new Property([
            'type' => Property::TYPE_APARTMENT,
            'is_shared_accommodation' => true,
        ]);
        $nonSharedApartment = new Property([
            'type' => Property::TYPE_APARTMENT,
            'is_shared_accommodation' => false,
        ]);

        $this->assertTrue($sharedApartment->canHaveRooms());
        $this->assertFalse($nonSharedApartment->canHaveRooms());
    }

    public function test_can_have_rooms_is_false_for_a_parking_even_if_the_shared_flag_is_set(): void
    {
        $parking = new Property([
            'type' => Property::TYPE_PARKING,
            'is_shared_accommodation' => true,
        ]);

        $this->assertFalse($parking->canHaveRooms());
    }

    public function test_type_label_returns_the_known_french_label(): void
    {
        $property = new Property(['type' => Property::TYPE_HOUSE]);

        $this->assertSame('Maison', $property->typeLabel());
    }

    public function test_type_label_falls_back_to_the_raw_value_for_an_unknown_type(): void
    {
        $property = new Property(['type' => 'loft']);

        $this->assertSame('loft', $property->typeLabel());
    }
}
