<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Services\AddressGeocoder;
use Illuminate\Console\Command;

class GeocodeAddresses extends Command
{
    protected $signature = 'addresses:geocode {--force : Recalculer les coordonnees existantes}';

    protected $description = 'Geocoder les adresses utilisees par les immeubles et les maisons';

    public function handle(AddressGeocoder $geocoder): int
    {
        $query = Address::query();
        if (! $this->option('force')) {
            $query->whereNull('geocoded_at');
        }

        $addresses = $query->get();
        foreach ($addresses as $address) {
            if ($geocoder->geocode($address)) {
                $this->info("Adresse {$address->id} geocodee : {$address->latitude}, {$address->longitude}.");
            } else {
                $this->warn("Adresse {$address->id} non geocodee : aucun resultat ou service indisponible.");
            }
        }

        $this->info("{$addresses->count()} adresse(s) traitee(s).");

        return self::SUCCESS;
    }
}
