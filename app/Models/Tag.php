<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name'])]
class Tag extends Model
{
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class);
    }

    /**
     * Parse a comma-separated string of tag names into unique, trimmed names.
     */
    public static function parseNames(?string $tags): array
    {
        return collect(explode(',', (string) $tags))
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->map(fn (string $tag): string => Str::limit($tag, 50, ''))
            ->unique()
            ->values()
            ->all();
    }

    public static function idsFromText(?string $tags): array
    {
        return collect(self::parseNames($tags))
            ->map(fn (string $name): int => self::firstOrCreate(['name' => $name])->id)
            ->all();
    }
}
