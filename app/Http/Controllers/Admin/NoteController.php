<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Note;
use App\Models\Property;
use App\Models\PropertyRoom;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function storeBuilding(Request $request, Building $building): RedirectResponse
    {
        return $this->store($request, $building, 'buildings.show');
    }

    public function storeProperty(Request $request, Property $property): RedirectResponse
    {
        return $this->store($request, $property, 'properties.show');
    }

    public function storePropertyRoom(Request $request, Property $property, PropertyRoom $room): RedirectResponse
    {
        abort_unless($room->property_id === $property->id, 404);

        return $this->store($request, $room, 'property-rooms.show', [$property, $room]);
    }

    public function storeTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        return $this->store($request, $tenant, 'tenants.show');
    }

    public function update(Request $request, Note $note): RedirectResponse
    {
        abort_unless($this->canModify($note), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note->update([
            'body' => $validated['body'],
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'La note a été modifiée.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        abort_unless($this->canModify($note), 403);

        $note->delete();

        return back()->with('success', 'La note a été supprimée.');
    }

    private function store(Request $request, object $notable, string $route, ?array $parameters = null): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $notable->notes()->create([
            'body' => $validated['body'],
            'created_by' => auth()->id(),
        ]);

        return to_route($route, $parameters ?? $notable)->with('success', 'La note a été ajoutée.');
    }

    private function canModify(Note $note): bool
    {
        $user = auth()->user();

        $hasParentPermission = match ($note->notable_type) {
            Building::class => $user->can('manage buildings'),
            Property::class => $user->can('manage properties'),
            PropertyRoom::class => $user->can('manage properties'),
            Tenant::class => $user->can('manage tenants'),
            default => false,
        };

        if (! $hasParentPermission) {
            return false;
        }

        return $note->created_by === $user->id || $user->hasRole('admin');
    }
}
