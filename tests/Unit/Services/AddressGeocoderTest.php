<?php

namespace Tests\Unit\Services;

use App\Models\Address;
use App\Services\AddressGeocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressGeocoderTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_is_disabled_in_the_testing_environment(): void
    {
        Http::fake();
        $address = $this->createAddress();

        $result = (new AddressGeocoder())->geocode($address);

        $this->assertFalse($result);
        Http::assertNothingSent();
        $this->assertNull($address->fresh()->latitude);
    }

    public function test_geocode_updates_the_address_coordinates_on_success(): void
    {
        $this->useNonTestingEnvironment();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '48.8566', 'lon' => '2.3522'],
            ]),
        ]);
        $address = $this->createAddress();

        $result = (new AddressGeocoder())->geocode($address);

        $this->assertTrue($result);
        $address->refresh();
        $this->assertEqualsWithDelta(48.8566, (float) $address->latitude, 0.0001);
        $this->assertEqualsWithDelta(2.3522, (float) $address->longitude, 0.0001);
        $this->assertNotNull($address->geocoded_at);
    }

    public function test_geocode_returns_false_on_a_failed_http_response(): void
    {
        $this->useNonTestingEnvironment();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 500),
        ]);
        $address = $this->createAddress();

        $result = (new AddressGeocoder())->geocode($address);

        $this->assertFalse($result);
        $this->assertNull($address->fresh()->latitude);
    }

    public function test_geocode_returns_false_when_no_result_is_found(): void
    {
        $this->useNonTestingEnvironment();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);
        $address = $this->createAddress();

        $result = (new AddressGeocoder())->geocode($address);

        $this->assertFalse($result);
        $this->assertNull($address->fresh()->latitude);
    }

    public function test_geocode_returns_false_on_a_malformed_response_missing_coordinates(): void
    {
        $this->useNonTestingEnvironment();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['display_name' => 'Paris, France'],
            ]),
        ]);
        $address = $this->createAddress();

        $result = (new AddressGeocoder())->geocode($address);

        $this->assertFalse($result);
        $this->assertNull($address->fresh()->latitude);
    }

    public function test_geocode_returns_false_on_a_network_exception(): void
    {
        $this->useNonTestingEnvironment();
        Http::fake(function (): never {
            throw new ConnectionException('Connection timed out.');
        });
        $address = $this->createAddress();

        $result = (new AddressGeocoder())->geocode($address);

        $this->assertFalse($result);
        $this->assertNull($address->fresh()->latitude);
    }

    private function useNonTestingEnvironment(): void
    {
        $this->app->instance('env', 'local');
    }

    private function createAddress(): Address
    {
        return Address::create([
            'line1' => '1 rue du Test',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);
    }
}
