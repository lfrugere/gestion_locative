<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'reference',
    'name',
    'type',
    'building_id',
    'bank_account_id',
    'address_id',
    'floor',
    'surface_m2',
    'is_shared_accommodation',
    'status',
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
            'is_shared_accommodation' => 'boolean',
        ];
    }

    public function canBeSharedAccommodation(): bool
    {
        return in_array($this->type, [
            self::TYPE_APARTMENT,
            self::TYPE_HOUSE,
        ], true);
    }

    public function canHaveRooms(): bool
    {
        return $this->is_shared_accommodation && $this->canBeSharedAccommodation();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(PropertyRoom::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('date');
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_manager');
    }

    public function isManagedBy(User $user): bool
    {
        return $this->managers()->whereKey($user->id)->exists();
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
