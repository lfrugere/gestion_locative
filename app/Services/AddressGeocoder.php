<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Facades\Http;
use Throwable;

class AddressGeocoder
{
    public function geocode(Address $address): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(3)
                ->withHeaders(['User-Agent' => config('app.name').' address geocoder'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => implode(', ', array_filter([
                        $address->line1,
                        $address->postal_code.' '.$address->city,
                        $address->country,
                    ])),
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);
        } catch (Throwable) {
            return false;
        }

        $results = $response->json();
        $result = is_array($results) ? ($results[0] ?? null) : null;

        if (! $response->successful() || ! is_array($result) || ! isset($result['lat'], $result['lon'])) {
            return false;
        }

        $address->update([
            'latitude' => $result['lat'],
            'longitude' => $result['lon'],
            'geocoded_at' => now(),
        ]);

        return true;
    }
}
