<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Building;
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
            'buildings' => Building::with('address')
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
            'building' => $building->load(['address', 'properties']),
        ]);
    }

    public function edit(Building $building): View
    {
        return view('admin.buildings.edit', [
            'building' => $building->load('address'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, Building $building): RedirectResponse
    {
        return $this->save($request, $building);
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

    private function save(Request $request, ?Building $building = null): RedirectResponse
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
            'address.country' => ['required', 'string', 'size:2'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $building): void {
            if ($building) {
                $building->address()->update($validated['address']);
                $building->update([
                    'reference' => $validated['reference'],
                    'name' => $validated['name'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                return;
            }

            $address = Address::create($validated['address']);
            Building::create([
                'reference' => $validated['reference'],
                'name' => $validated['name'],
                'address_id' => $address->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return to_route('admin.buildings.index')->with(
            'success',
            $building ? 'L’immeuble a été modifié.' : 'L’immeuble a été créé.',
        );
    }
}
