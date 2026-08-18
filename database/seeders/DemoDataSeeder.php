<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed fictional portfolio and tenant records for local demonstrations.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $buildings = [
                [
                    'reference' => 'IMM-PAR-001',
                    'name' => 'Residence des Tilleuls',
                    'address' => [
                        'line1' => '14 rue des Tilleuls',
                        'line2' => null,
                        'postal_code' => '75015',
                        'city' => 'Paris',
                        'country' => 'FR',
                        'latitude' => 48.8412500,
                        'longitude' => 2.3009800,
                        'geocoded_at' => now(),
                    ],
                    'notes' => 'Petit immeuble familial proche des commerces et du metro.',
                ],
                [
                    'reference' => 'IMM-LYO-002',
                    'name' => 'Immeuble Croix-Rousse',
                    'address' => [
                        'line1' => '8 montee Saint-Sebastien',
                        'line2' => null,
                        'postal_code' => '69001',
                        'city' => 'Lyon',
                        'country' => 'FR',
                        'latitude' => 45.7731600,
                        'longitude' => 4.8324100,
                        'geocoded_at' => now(),
                    ],
                    'notes' => 'Copropriete ancienne avec parties communes renovees.',
                ],
            ];

            foreach ($buildings as $buildingData) {
                $this->upsertBuilding($buildingData);
            }

            $properties = [
                [
                    'reference' => 'APP-PAR-001-01',
                    'name' => 'Appartement T2 Tilleuls',
                    'type' => Property::TYPE_APARTMENT,
                    'building_reference' => 'IMM-PAR-001',
                    'floor' => '1er',
                    'surface_m2' => 42.50,
                    'is_shared_accommodation' => false,
                    'status' => 'active',
                    'notes' => 'Appartement lumineux sur cour, cuisine separee.',
                ],
                [
                    'reference' => 'APP-PAR-001-02',
                    'name' => 'Studio Tilleuls',
                    'type' => Property::TYPE_APARTMENT,
                    'building_reference' => 'IMM-PAR-001',
                    'floor' => '3e',
                    'surface_m2' => 24.10,
                    'is_shared_accommodation' => false,
                    'status' => 'active',
                    'notes' => 'Studio meuble avec espace nuit en alcove.',
                ],
                [
                    'reference' => 'PKG-PAR-001-01',
                    'name' => 'Parking sous-sol Tilleuls',
                    'type' => Property::TYPE_PARKING,
                    'building_reference' => 'IMM-PAR-001',
                    'floor' => 'Sous-sol -1',
                    'surface_m2' => 12.00,
                    'is_shared_accommodation' => false,
                    'status' => 'active',
                    'notes' => 'Place large proche de la rampe.',
                ],
                [
                    'reference' => 'APP-LYO-002-01',
                    'name' => 'T3 Croix-Rousse',
                    'type' => Property::TYPE_APARTMENT,
                    'building_reference' => 'IMM-LYO-002',
                    'floor' => '2e',
                    'surface_m2' => 63.80,
                    'is_shared_accommodation' => true,
                    'status' => 'inactive',
                    'notes' => 'Rafraichissement peinture a prevoir avant remise en location.',
                ],
                [
                    'reference' => 'MAI-NAN-001',
                    'name' => 'Maison des Acacias',
                    'type' => Property::TYPE_HOUSE,
                    'building_reference' => null,
                    'address' => [
                        'line1' => '22 allee des Acacias',
                        'line2' => null,
                        'postal_code' => '44000',
                        'city' => 'Nantes',
                        'country' => 'FR',
                        'latitude' => 47.2172500,
                        'longitude' => -1.5533600,
                        'geocoded_at' => now(),
                    ],
                    'floor' => null,
                    'surface_m2' => 96.40,
                    'is_shared_accommodation' => true,
                    'status' => 'active',
                    'notes' => 'Maison individuelle avec jardin et garage.',
                ],
                [
                    'reference' => 'MAI-BDX-002',
                    'name' => 'Echoppe Saint-Augustin',
                    'type' => Property::TYPE_HOUSE,
                    'building_reference' => null,
                    'address' => [
                        'line1' => '5 rue du Tondu',
                        'line2' => null,
                        'postal_code' => '33000',
                        'city' => 'Bordeaux',
                        'country' => 'FR',
                        'latitude' => 44.8377900,
                        'longitude' => -0.5791800,
                        'geocoded_at' => now(),
                    ],
                    'floor' => null,
                    'surface_m2' => 78.25,
                    'is_shared_accommodation' => false,
                    'status' => 'inactive',
                    'notes' => 'Maison de ville avec petite cour interieure.',
                ],
            ];

            foreach ($properties as $propertyData) {
                $this->upsertProperty($propertyData);
            }

            $rooms = [
                'APP-LYO-002-01' => [
                    ['name' => 'Chambre cote cour', 'surface_m2' => 12.40, 'status' => 'active', 'notes' => 'Chambre calme avec rangements integres.'],
                    ['name' => 'Chambre cote rue', 'surface_m2' => 11.80, 'status' => 'active', 'notes' => null],
                    ['name' => 'Salon partage', 'surface_m2' => 18.20, 'status' => 'active', 'notes' => 'Piece commune principale.'],
                    ['name' => 'Cuisine commune', 'surface_m2' => 8.60, 'status' => 'active', 'notes' => null],
                ],
                'MAI-NAN-001' => [
                    ['name' => 'Chambre jardin', 'surface_m2' => 13.50, 'status' => 'active', 'notes' => 'Vue sur le jardin.'],
                    ['name' => 'Chambre etage', 'surface_m2' => 14.10, 'status' => 'active', 'notes' => null],
                    ['name' => 'Salle de bain partagee', 'surface_m2' => 6.30, 'status' => 'active', 'notes' => null],
                ],
            ];

            foreach ($rooms as $propertyReference => $propertyRooms) {
                $property = Property::where('reference', $propertyReference)->first();

                if (! $property?->canHaveRooms()) {
                    continue;
                }

                foreach ($propertyRooms as $roomData) {
                    $property->rooms()->updateOrCreate(
                        ['name' => $roomData['name']],
                        $roomData,
                    );
                }
            }

            $tenants = [
                [
                    'civility' => Tenant::CIVILITY_MRS,
                    'first_name' => 'Camille',
                    'last_name' => 'Martin',
                    'birth_date' => '1991-04-12',
                    'status' => Tenant::STATUS_ACTIVE,
                    'status_changed_at' => Carbon::parse('2026-06-01 09:00:00'),
                ],
                [
                    'civility' => Tenant::CIVILITY_MR,
                    'first_name' => 'Hugo',
                    'last_name' => 'Bernard',
                    'birth_date' => '1987-11-03',
                    'status' => Tenant::STATUS_VALIDATING,
                    'status_changed_at' => Carbon::parse('2026-08-10 14:30:00'),
                ],
                [
                    'civility' => Tenant::CIVILITY_MRS,
                    'first_name' => 'Lea',
                    'last_name' => 'Petit',
                    'birth_date' => '1998-02-27',
                    'status' => Tenant::STATUS_CANDIDATE,
                    'status_changed_at' => Carbon::parse('2026-08-14 11:15:00'),
                ],
                [
                    'civility' => Tenant::CIVILITY_MR,
                    'first_name' => 'Nicolas',
                    'last_name' => 'Moreau',
                    'birth_date' => '1979-09-18',
                    'status' => Tenant::STATUS_FORMER,
                    'status_changed_at' => Carbon::parse('2026-05-20 16:00:00'),
                ],
                [
                    'civility' => Tenant::CIVILITY_OTHER,
                    'first_name' => 'Sasha',
                    'last_name' => 'Roux',
                    'birth_date' => null,
                    'status' => Tenant::STATUS_REFUSED,
                    'status_changed_at' => Carbon::parse('2026-07-25 10:45:00'),
                ],
            ];

            foreach ($tenants as $tenantData) {
                Tenant::updateOrCreate(
                    [
                        'first_name' => $tenantData['first_name'],
                        'last_name' => $tenantData['last_name'],
                    ],
                    $tenantData,
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertBuilding(array $data): Building
    {
        $building = Building::where('reference', $data['reference'])->first();
        $address = $building?->address ?? Address::create($data['address']);

        $address->update($data['address']);

        return Building::updateOrCreate(
            ['reference' => $data['reference']],
            [
                'name' => $data['name'],
                'address_id' => $address->id,
                'notes' => $data['notes'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertProperty(array $data): Property
    {
        $property = Property::where('reference', $data['reference'])->first();
        $addressId = null;

        if ($data['type'] === Property::TYPE_HOUSE) {
            $address = $property?->address ?? Address::create($data['address']);
            $address->update($data['address']);
            $addressId = $address->id;
        }

        $buildingId = null;

        if ($data['building_reference']) {
            $buildingId = Building::where('reference', $data['building_reference'])->value('id');
        }

        return Property::updateOrCreate(
            ['reference' => $data['reference']],
            [
                'name' => $data['name'],
                'type' => $data['type'],
                'building_id' => $buildingId,
                'address_id' => $addressId,
                'floor' => $data['floor'],
                'surface_m2' => $data['surface_m2'],
                'is_shared_accommodation' => $data['is_shared_accommodation'],
                'status' => $data['status'],
                'notes' => $data['notes'],
            ],
        );
    }
}
