<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['reference', 'name', 'address_id', 'notes'])]
class Building extends Model
{
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
