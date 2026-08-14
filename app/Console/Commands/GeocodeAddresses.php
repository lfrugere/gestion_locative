<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Services\AddressGeocoder;
use Illuminate\Console\Command;

class GeocodeAddresses extends Command
{
    protected $signature = 'addresses:geocode {--force : Recalculer les coordonnées existantes}';

    protected $description = 'Géocoder les adresses utilisées par les immeubles et les maisons';

    public function handle(AddressGeocoder $geocoder): int
    {
        $query = Address::query();
        if (! $this->option('force')) {
            $query->whereNull('geocoded_at');
        }

        $addresses = $query->get();
        foreach ($addresses as $address) {
            $geocoder->geocode($address);
            $this->line("Adresse {$address->id} traitée.");
        }

        $this->info("{$addresses->count()} adresse(s) traitée(s).");

        return self::SUCCESS;
    }
}
