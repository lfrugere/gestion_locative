<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyRoom;
use App\Services\MediaManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PropertyRoomController extends Controller
{
    public function create(Property $property): View
    {
        abort_unless($property->canHaveRooms(), 404);

        return view('admin.property-rooms.create', [
            'property' => $property,
            'room' => null,
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        abort_unless($property->canHaveRooms(), 404);

        $property->rooms()->create($this->validated($request));

        return to_route('admin.properties.show', $property)
            ->with('success', 'La pièce a été ajoutée.');
    }

    public function show(Property $property, PropertyRoom $room): View
    {
        $this->ensureRoomBelongsToProperty($property, $room);

        return view('admin.property-rooms.show', [
            'property' => $property,
            'room' => $room->load(['media.tags', 'notes.author', 'notes.editor']),
        ]);
    }

    public function edit(Property $property, PropertyRoom $room): View
    {
        $this->ensureRoomBelongsToProperty($property, $room);
        abort_unless($property->canHaveRooms(), 404);

        return view('admin.property-rooms.edit', [
            'property' => $property,
            'room' => $room,
        ]);
    }

    public function update(Request $request, Property $property, PropertyRoom $room): RedirectResponse
    {
        $this->ensureRoomBelongsToProperty($property, $room);
        abort_unless($property->canHaveRooms(), 404);

        $room->update($this->validated($request));

        return to_route('admin.property-rooms.show', [$property, $room])
            ->with('success', 'La pièce a été modifiée.');
    }

    public function destroy(Property $property, PropertyRoom $room, MediaManager $mediaManager): RedirectResponse
    {
        $this->ensureRoomBelongsToProperty($property, $room);

        $room->media->each(fn ($media) => $mediaManager->delete($media));
        $room->delete();

        return to_route('admin.properties.show', $property)
            ->with('success', 'La pièce a été supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surface_m2' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function ensureRoomBelongsToProperty(Property $property, PropertyRoom $room): void
    {
        abort_unless($room->property_id === $property->id, 404);
    }
}
