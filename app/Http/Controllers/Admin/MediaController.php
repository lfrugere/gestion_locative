<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyRoom;
use App\Models\Tenant;
use App\Services\MediaManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function storeBuilding(Request $request, Building $building, MediaManager $manager): RedirectResponse
    {
        $this->authorize('createForBuilding', Media::class);

        return $this->store($request, $building, $manager, 'buildings.show');
    }

    public function storeProperty(Request $request, Property $property, MediaManager $manager): RedirectResponse
    {
        $this->authorize('createForProperty', [Media::class, $property]);

        return $this->store($request, $property, $manager, 'properties.show');
    }

    public function storePropertyRoom(Request $request, Property $property, PropertyRoom $room, MediaManager $manager): RedirectResponse
    {
        abort_unless($room->property_id === $property->id, 404);
        $this->authorize('createForPropertyRoom', Media::class);

        return $this->store($request, $room, $manager, 'property-rooms.show', [$property, $room]);
    }

    public function storeTenant(Request $request, Tenant $tenant, MediaManager $manager): RedirectResponse
    {
        $this->authorize('createForTenant', [Media::class, $tenant]);

        if ($request->string('kind')->toString() === Media::KIND_PHOTO
            && $tenant->media()->where('kind', Media::KIND_PHOTO)->exists()) {
            return back()->withInput()->withErrors([
                'file' => 'Un locataire ne peut avoir qu’une seule photo d’identité. Supprimez-la avant d’en ajouter une autre.',
            ]);
        }

        return $this->store($request, $tenant, $manager, 'tenants.show');
    }

    public function storeInvoice(Request $request, Property $property, Invoice $invoice, MediaManager $manager): RedirectResponse
    {
        abort_unless($invoice->property_id === $property->id, 404);
        $this->authorize('createForInvoice', [Media::class, $property]);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ]);

        $manager->store($invoice, $validated['file'], Media::KIND_DOCUMENT, Media::TYPE_OTHER, null, null);

        return to_route('properties.show', $property)->with('success', 'La pièce jointe a été ajoutée.');
    }

    public function update(Request $request, Media $media, MediaManager $manager): RedirectResponse
    {
        $this->authorize('update', $media);

        $validated = $request->validate([
            'type' => ['required', Rule::in($media->isPhoto()
                ? [Media::TYPE_PHOTOS]
                : Media::documentTypesFor($media->mediable))],
            'display_name' => ['required', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);
        $type = $media->isPhoto() ? Media::TYPE_PHOTOS : $validated['type'];
        $manager->update($media, $type, $validated['display_name'], $validated['tags'] ?? null);

        return back()->with('success', 'La pièce jointe a été modifiée.');
    }

    public function setPrimary(Media $media, MediaManager $manager): RedirectResponse
    {
        abort_unless($media->isPhoto(), 403);
        $this->authorize('update', $media);
        $manager->setPrimary($media);

        return back()->with('success', 'La photo principale a été définie.');
    }

    public function destroy(Media $media, MediaManager $manager): RedirectResponse
    {
        $this->authorize('update', $media);
        $manager->delete($media);

        return back()->with('success', 'Le fichier a été supprimé.');
    }

    public function download(Media $media): StreamedResponse
    {
        $this->authorize('view', $media);

        return Storage::disk($media->disk)->download($media->path, $media->display_name);
    }

    private function store(Request $request, object $mediable, MediaManager $manager, string $route, ?array $parameters = null): RedirectResponse
    {
        $kind = $request->string('kind')->toString();
        $defaultType = $kind === Media::KIND_PHOTO ? Media::TYPE_PHOTOS : Media::TYPE_OTHER;
        $types = $kind === Media::KIND_PHOTO
            ? [Media::TYPE_PHOTOS]
            : Media::documentTypesFor($mediable);
        $validated = $request->validate([
            'kind' => ['required', Rule::in([Media::KIND_PHOTO, Media::KIND_DOCUMENT])],
            'type' => ['nullable', Rule::in($types)],
            'file' => [
                'required', 'file', 'max:20480',
                $kind === Media::KIND_PHOTO ? 'mimes:jpg,jpeg,png,webp' : 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $type = $kind === Media::KIND_PHOTO
            ? Media::TYPE_PHOTOS
            : ($validated['type'] ?? $defaultType);

        $manager->store($mediable, $validated['file'], $validated['kind'], $type, $validated['display_name'] ?? null, $validated['tags'] ?? null);

        return to_route($route, $parameters ?? $mediable)->with('success', 'Le fichier a été ajouté.');
    }

}
