<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'statistics' => [
                'buildings' => Building::count(),
                'properties' => Property::count(),
                'apartments' => Property::where('type', Property::TYPE_APARTMENT)->count(),
                'houses' => Property::where('type', Property::TYPE_HOUSE)->count(),
                'parkings' => Property::where('type', Property::TYPE_PARKING)->count(),
                'activeProperties' => Property::where('status', 'active')->count(),
                'tenants' => Tenant::count(),
            ],
            'attention' => [
                'propertiesWithoutPhoto' => Property::whereDoesntHave('media', fn ($query) => $query->where('kind', 'photo'))->count(),
                'buildingsWithoutPhoto' => Building::whereDoesntHave('media', fn ($query) => $query->where('kind', 'photo'))->count(),
                'addressesWithoutCoordinates' => Address::whereNull('latitude')->orWhereNull('longitude')->count(),
            ],
            'recentBuildings' => Building::withCount('properties')->latest()->take(4)->get(),
            'recentProperties' => Property::with('building')->latest()->take(5)->get(),
            'recentTenants' => Tenant::latest()->take(5)->get(),
        ]);
    }
}
