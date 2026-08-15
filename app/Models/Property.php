<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'reference',
    'name',
    'type',
    'building_id',
    'address_id',
    'floor',
    'surface_m2',
    'status',
    'notes',
])]
class Property extends Model
{
    public const TYPE_APARTMENT = 'apartment';

    public const TYPE_HOUSE = 'house';

    public const TYPE_PARKING = 'parking';

    public const TYPES = [
        self::TYPE_APARTMENT,
        self::TYPE_HOUSE,
        self::TYPE_PARKING,
    ];

    public const TYPE_LABELS = [
        self::TYPE_APARTMENT => 'Appartement',
        self::TYPE_HOUSE => 'Maison',
        self::TYPE_PARKING => 'Parking',
    ];

    protected function casts(): array
    {
        return [
            'surface_m2' => 'decimal:2',
        ];
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
