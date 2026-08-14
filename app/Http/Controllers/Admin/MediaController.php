<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Media;
use App\Models\Property;
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
        return $this->store($request, $building, $manager, 'admin.buildings.show');
    }

    public function storeProperty(Request $request, Property $property, MediaManager $manager): RedirectResponse
    {
        return $this->store($request, $property, $manager, 'admin.properties.show');
    }

    public function update(Request $request, Media $media, MediaManager $manager): RedirectResponse
    {
        abort_unless($this->canManage($media), 403);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);
        $manager->update($media, $validated['display_name'], $validated['tags'] ?? null);

        return back()->with('success', 'La pièce jointe a été modifiée.');
    }

    public function setPrimary(Media $media, MediaManager $manager): RedirectResponse
    {
        abort_unless($media->isPhoto() && $this->canManage($media), 403);
        $manager->setPrimary($media);

        return back()->with('success', 'La photo principale a été définie.');
    }

    public function destroy(Media $media, MediaManager $manager): RedirectResponse
    {
        abort_unless($this->canManage($media), 403);
        $manager->delete($media);

        return back()->with('success', 'Le fichier a été supprimé.');
    }

    public function download(Media $media): StreamedResponse
    {
        abort_unless($this->canView($media), 403);

        return Storage::disk($media->disk)->download($media->path, $media->display_name);
    }

    private function store(Request $request, object $mediable, MediaManager $manager, string $route): RedirectResponse
    {
        $kind = $request->string('kind')->toString();
        $validated = $request->validate([
            'kind' => ['required', Rule::in([Media::KIND_PHOTO, Media::KIND_DOCUMENT])],
            'file' => [
                'required', 'file', 'max:10240',
                $kind === Media::KIND_PHOTO ? 'mimes:jpg,jpeg,png,webp' : 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $manager->store($mediable, $validated['file'], $validated['kind'], $validated['display_name'] ?? null, $validated['tags'] ?? null);

        return to_route($route, $mediable)->with('success', 'Le fichier a été ajouté.');
    }

    private function canView(Media $media): bool
    {
        return auth()->user()->can($media->mediable_type === Building::class ? 'view buildings' : 'view properties');
    }

    private function canManage(Media $media): bool
    {
        return auth()->user()->can($media->mediable_type === Building::class ? 'manage buildings' : 'manage properties');
    }
}
