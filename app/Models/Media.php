<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'mediable_type', 'mediable_id', 'kind', 'type', 'disk', 'path', 'original_name',
    'display_name', 'mime_type', 'size', 'is_primary', 'metadata',
])]
class Media extends Model
{
    public const KIND_PHOTO = 'photo';

    public const KIND_DOCUMENT = 'document';

    public const TYPE_PHOTOS = 'photos';

    public const TYPE_IDENTITY = 'identity';

    public const TYPE_BANK_DETAILS = 'bank_details';

    public const TYPE_INSURANCE = 'insurance';

    public const TYPE_DIAGNOSTICS = 'diagnostics';

    public const TYPE_OTHER = 'other';

    public const TYPE_LABELS = [
        self::TYPE_PHOTOS => 'Photos',
        self::TYPE_IDENTITY => 'Identité',
        self::TYPE_BANK_DETAILS => 'RIB',
        self::TYPE_INSURANCE => 'Assurance',
        self::TYPE_DIAGNOSTICS => 'Diagnostics',
        self::TYPE_OTHER => 'Autre',
    ];

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

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public static function typesFor(object $mediable): array
    {
        return match (true) {
            $mediable instanceof Tenant => [
                self::TYPE_PHOTOS,
                self::TYPE_IDENTITY,
                self::TYPE_BANK_DETAILS,
                self::TYPE_INSURANCE,
                self::TYPE_OTHER,
            ],
            $mediable instanceof Building, $mediable instanceof Property => [
                self::TYPE_PHOTOS,
                self::TYPE_DIAGNOSTICS,
                self::TYPE_INSURANCE,
                self::TYPE_OTHER,
            ],
            default => [],
        };
    }

    public static function documentTypesFor(object $mediable): array
    {
        return array_values(array_diff(self::typesFor($mediable), [self::TYPE_PHOTOS]));
    }
}
