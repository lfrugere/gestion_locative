<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaManager
{
    public function store(object $mediable, UploadedFile $file, string $kind, ?string $displayName, ?string $tags): Media
    {
        $directory = 'media/'.Str::snake(class_basename($mediable)).'/'.$mediable->getKey();
        $extension = strtolower($file->guessExtension() ?: $file->extension());
        $path = $file->storeAs($directory, Str::uuid().'.'.$extension, 'local');

        $media = DB::transaction(function () use ($mediable, $file, $kind, $displayName, $tags, $path): Media {
            $media = $mediable->media()->create([
                'kind' => $kind,
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

    public function setPrimary(Media $media): void
    {
        DB::transaction(function () use ($media): void {
            $media->mediable->media()->where('kind', Media::KIND_PHOTO)->update(['is_primary' => false]);
            $media->update(['is_primary' => true]);
        });
    }

    public function update(Media $media, ?string $displayName, ?string $tags): void
    {
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
