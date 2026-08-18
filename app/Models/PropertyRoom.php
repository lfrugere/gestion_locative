<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'property_id',
    'name',
    'type',
    'surface_m2',
    'status',
    'notes',
])]
class PropertyRoom extends Model
{
    public const TYPE_BEDROOM = 'bedroom';

    public const TYPE_LIVING_ROOM = 'living_room';

    public const TYPE_KITCHEN = 'kitchen';

    public const TYPE_BATHROOM = 'bathroom';

    public const TYPE_TOILET = 'toilet';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_BEDROOM,
        self::TYPE_LIVING_ROOM,
        self::TYPE_KITCHEN,
        self::TYPE_BATHROOM,
        self::TYPE_TOILET,
        self::TYPE_OTHER,
    ];

    public const TYPE_LABELS = [
        self::TYPE_BEDROOM => 'Chambre',
        self::TYPE_LIVING_ROOM => 'Salon',
        self::TYPE_KITCHEN => 'Cuisine',
        self::TYPE_BATHROOM => 'Salle de bain',
        self::TYPE_TOILET => 'WC',
        self::TYPE_OTHER => 'Autre',
    ];

    protected function casts(): array
    {
        return [
            'surface_m2' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
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
