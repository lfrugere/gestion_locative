<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Services\AddressGeocoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Property::class);

        return view('admin.properties.index', [
            'properties' => Property::with(['building', 'address', 'media' => fn ($query) => $query->where('kind', 'photo')->where('is_primary', true)])
                ->orderBy('reference')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Property::class);

        return view('admin.properties.create', [
            'buildings' => Building::orderBy('name')->get(),
            'bankAccounts' => BankAccount::orderBy('label')->get(),
        ]);
    }

    public function show(Property $property): View
    {
        $this->authorize('view', $property);

        return view('admin.properties.show', [
            'property' => $property->load([
                'building.address',
                'bankAccount',
                'address',
                'media.tags',
                'rooms.media',
                'notes.author',
                'notes.editor',
                'managers',
                'invoices.bankTransaction',
                'invoices.media',
                'invoices.tags',
            ]),
            'managers' => User::role('gestionnaire')->orderBy('name')->get(),
        ]);
    }

    public function edit(Property $property): View
    {
        $this->authorize('update', $property);

        return view('admin.properties.edit', [
            'property' => $property->load('address'),
            'buildings' => Building::orderBy('name')->get(),
            'bankAccounts' => BankAccount::orderBy('label')->get(),
        ]);
    }

    public function store(Request $request, AddressGeocoder $geocoder): RedirectResponse
    {
        $this->authorize('create', Property::class);

        return $this->save($request, null, $geocoder);
    }

    public function update(Request $request, Property $property, AddressGeocoder $geocoder): RedirectResponse
    {
        $this->authorize('update', $property);

        return $this->save($request, $property, $geocoder);
    }

    public function updateManagers(Request $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $validated = $request->validate([
            'managers' => ['sometimes', 'array'],
            'managers.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        $property->managers()->sync($validated['managers'] ?? []);

        return back()->with('success', 'Les gestionnaires du bien ont été mis à jour.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        DB::transaction(function () use ($property): void {
            $address = $property->address;
            $property->delete();
            $address?->delete();
        });

        return to_route('properties.index')
            ->with('success', 'Le bien a été supprimé.');
    }

    private function save(Request $request, ?Property $property, AddressGeocoder $geocoder): RedirectResponse
    {
        $type = $request->string('type')->toString();
        $requiresBuilding = in_array($type, [
            Property::TYPE_APARTMENT,
            Property::TYPE_PARKING,
        ], true);

        $validated = $request->validate([
            'reference' => [
                'required', 'string', 'max:50',
                Rule::unique('properties', 'reference')->ignore($property),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Property::TYPES)],
            'building_id' => [
                'nullable', 'integer', 'exists:buildings,id',
                Rule::requiredIf($requiresBuilding),
                Rule::prohibitedIf($type === Property::TYPE_HOUSE),
            ],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'address.line1' => [Rule::requiredIf($type === Property::TYPE_HOUSE), 'nullable', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => [Rule::requiredIf($type === Property::TYPE_HOUSE), 'nullable', 'string', 'max:20'],
            'address.city' => [Rule::requiredIf($type === Property::TYPE_HOUSE), 'nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:30'],
            'surface_m2' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_shared_accommodation' => ['sometimes', 'boolean', Rule::prohibitedIf($type === Property::TYPE_PARKING)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        if ($type === Property::TYPE_HOUSE) {
            $validated['address']['country'] = 'FR';
        }

        $isSharedAccommodation = $type !== Property::TYPE_PARKING && $request->boolean('is_shared_accommodation');
        if ($property && ! $isSharedAccommodation && $property->rooms()->exists()) {
            return back()->withInput()->withErrors([
                'is_shared_accommodation' => 'Impossible de désactiver la colocation tant que des pièces sont rattachées à ce bien.',
            ]);
        }

        $addressId = null;
        DB::transaction(function () use ($validated, $type, $property, $isSharedAccommodation, &$addressId): void {
            $addressId = null;

            if ($type === Property::TYPE_HOUSE) {
                if ($property?->address) {
                    $property->address()->update($validated['address']);
                    $addressId = $property->address_id;
                } else {
                    $addressId = Address::create($validated['address'])->id;
                }
            } elseif ($property?->address) {
                $oldAddress = $property->address;
                $property->update(['address_id' => null]);
                $oldAddress->delete();
            }

            $attributes = [
                'reference' => $validated['reference'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'building_id' => $validated['building_id'] ?? null,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'address_id' => $addressId,
                'floor' => $validated['floor'] ?? null,
                'surface_m2' => $validated['surface_m2'] ?? null,
                'is_shared_accommodation' => $isSharedAccommodation,
                'status' => $validated['status'],
            ];

            if ($property) {
                $property->update($attributes);
            } else {
                Property::create($attributes);
            }
        });

        if ($addressId) {
            $geocoder->geocode(Address::findOrFail($addressId));
        }

        return to_route('properties.index')->with(
            'success',
            $property ? 'Le bien a été modifié.' : 'Le bien a été créé.',
        );
    }
}
