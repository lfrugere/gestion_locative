<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyRoom;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaManager
{
    public function store(object $mediable, UploadedFile $file, string $kind, string $type, ?string $displayName, ?string $tags): Media
    {
        $directory = $this->directoryFor($mediable, $kind, $type);
        $extension = strtolower($file->guessExtension() ?: $file->extension());
        $path = $file->storeAs($directory, Str::uuid().'.'.$extension, 'local');

        $media = DB::transaction(function () use ($mediable, $file, $kind, $type, $displayName, $tags, $path): Media {
            $media = $mediable->media()->create([
                'kind' => $kind,
                'type' => $type,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'display_name' => $displayName ?: $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_primary' => $kind === Media::KIND_PHOTO && ! $mediable->media()->where('kind', $kind)->where('is_primary', true)->exists(),
            ]);

            $this->syncTags($media, $tags);

            return $media;
        });

        return $media;
    }

    private function directoryFor(object $mediable, string $kind, string $type): string
    {
        $owner = match (true) {
            $mediable instanceof Building => 'building/'.$mediable->reference,
            $mediable instanceof Property => 'property/'.$mediable->reference,
            $mediable instanceof PropertyRoom => 'property/'.$mediable->property->reference.'/room/'.$mediable->id,
            $mediable instanceof Tenant => 'tenant/'.$mediable->storage_key,
            default => throw new \InvalidArgumentException('Type de propriétaire de média non supporté.'),
        };

        return 'media/'.$owner.'/'.($kind === Media::KIND_PHOTO ? Media::TYPE_PHOTOS : $type);
    }

    public function setPrimary(Media $media): void
    {
        DB::transaction(function () use ($media): void {
            $media->mediable->media()->where('kind', Media::KIND_PHOTO)->update(['is_primary' => false]);
            $media->update(['is_primary' => true]);
        });
    }

    public function update(Media $media, string $type, ?string $displayName, ?string $tags): void
    {
        if ($media->type !== $type) {
            $directory = $this->directoryFor($media->mediable, $media->kind, $type);
            $newPath = $directory.'/'.basename($media->path);

            if (! Storage::disk($media->disk)->move($media->path, $newPath)) {
                throw new \RuntimeException('Le déplacement du fichier a échoué.');
            }

            $media->update(['type' => $type, 'path' => $newPath]);
        }

        $media->update(['display_name' => $displayName ?: $media->display_name]);
        $this->syncTags($media, $tags);
    }

    public function delete(Media $media): void
    {
        $replacement = $media->is_primary ? $media->mediable->media()
            ->where('kind', Media::KIND_PHOTO)
            ->where($media->getKeyName(), '!=', $media->id)
            ->oldest()->first() : null;

        DB::transaction(function () use ($media, $replacement): void {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
            $replacement?->update(['is_primary' => true]);
        });
    }

    private function syncTags(Media $media, ?string $tags): void
    {
        $names = collect(explode(',', (string) $tags))
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->map(fn (string $tag): string => Str::limit($tag, 50, ''))
            ->unique()
            ->values();

        $tagIds = $names->map(fn (string $name): int => Tag::firstOrCreate(['name' => $name])->id);
        $media->tags()->sync($tagIds);
    }
}
