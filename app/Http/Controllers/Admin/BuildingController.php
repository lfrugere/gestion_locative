<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Building;
use App\Services\AddressGeocoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function index(): View
    {
        return view('admin.buildings.index', [
            'buildings' => Building::with(['address', 'media' => fn ($query) => $query->where('kind', 'photo')->where('is_primary', true)])
                ->withCount('properties')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.buildings.create');
    }

    public function show(Building $building): View
    {
        return view('admin.buildings.show', [
            'building' => $building->load(['address', 'properties', 'media.tags', 'notes.author', 'notes.editor']),
        ]);
    }

    public function edit(Building $building): View
    {
        return view('admin.buildings.edit', [
            'building' => $building->load('address'),
        ]);
    }

    public function store(Request $request, AddressGeocoder $geocoder): RedirectResponse
    {
        return $this->save($request, null, $geocoder);
    }

    public function update(Request $request, Building $building, AddressGeocoder $geocoder): RedirectResponse
    {
        return $this->save($request, $building, $geocoder);
    }

    public function destroy(Building $building): RedirectResponse
    {
        if ($building->properties()->exists()) {
            return to_route('admin.buildings.index')
                ->with('error', 'Impossible de supprimer un immeuble auquel des biens sont rattachés.');
        }

        DB::transaction(function () use ($building): void {
            $address = $building->address;
            $building->delete();
            $address?->delete();
        });

        return to_route('admin.buildings.index')
            ->with('success', 'L’immeuble a été supprimé.');
    }

    private function save(Request $request, ?Building $building, AddressGeocoder $geocoder): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => [
                'required', 'string', 'max:50',
                Rule::unique('buildings', 'reference')->ignore($building),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address.line1' => ['required', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.city' => ['required', 'string', 'max:255'],
        ]);
        $validated['address']['country'] = 'FR';

        $addressId = $building?->address_id;
        DB::transaction(function () use ($validated, $building, &$addressId): void {
            if ($building) {
                $building->address()->update($validated['address']);
                $building->update([
                    'reference' => $validated['reference'],
                    'name' => $validated['name'],
                ]);

                return;
            }

            $address = Address::create($validated['address']);
            $addressId = $address->id;
            Building::create([
                'reference' => $validated['reference'],
                'name' => $validated['name'],
                'address_id' => $address->id,
            ]);
        });

        if ($addressId) {
            $geocoder->geocode(Address::findOrFail($addressId));
        }

        return to_route('admin.buildings.index')->with(
            'success',
            $building ? 'L’immeuble a été modifié.' : 'L’immeuble a été créé.',
        );
    }
}
