<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'mediable_type', 'mediable_id', 'kind', 'disk', 'path', 'original_name',
    'display_name', 'mime_type', 'size', 'is_primary', 'metadata',
])]
class Media extends Model
{
    public const KIND_PHOTO = 'photo';

    public const KIND_DOCUMENT = 'document';

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }

    public function isPhoto(): bool
    {
        return $this->kind === self::KIND_PHOTO;
    }
}
