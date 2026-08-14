<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Facades\Http;
use Throwable;

class AddressGeocoder
{
    public function geocode(Address $address): void
    {
        if (app()->environment('testing')) {
            return;
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
            return;
        }

        if (! $response->successful() || ! $response->json('0')) {
            return;
        }

        $result = $response->json('0');
        $address->update([
            'latitude' => $result['lat'],
            'longitude' => $result['lon'],
            'geocoded_at' => now(),
        ]);
    }
}
