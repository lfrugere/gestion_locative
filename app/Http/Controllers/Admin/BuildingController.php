<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:50', 'unique:buildings,reference'],
            'name' => ['required', 'string', 'max:255'],
            'address.line1' => ['required', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.country' => ['required', 'string', 'size:2'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated): void {
            $address = Address::create($validated['address']);

            Building::create([
                'reference' => $validated['reference'],
                'name' => $validated['name'],
                'address_id' => $address->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return to_route('admin.buildings.index')
            ->with('success', 'L’immeuble a été créé.');
    }
}
