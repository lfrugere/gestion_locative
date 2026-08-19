<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function myProperties(): View
    {
        $user = auth()->user();

        $properties = $user->hasRole('admin')
            ? Property::with(['building', 'address'])->orderBy('reference')->get()
            : $user->managedProperties()->with(['building', 'address'])->orderBy('reference')->get();

        return view('menus.mes-biens', ['properties' => $properties]);
    }

    public function showProperty(Property $property): View
    {
        $user = auth()->user();

        abort_unless(
            $user->hasRole('admin') || $property->managers()->whereKey($user->id)->exists(),
            403,
        );

        return view('menus.mes-biens-show', [
            'property' => $property->load([
                'building.address',
                'address',
                'media.tags',
                'rooms.media',
                'notes.author',
                'notes.editor',
            ]),
        ]);
    }
}
