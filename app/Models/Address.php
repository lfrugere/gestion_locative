<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['line1', 'line2', 'postal_code', 'city', 'country', 'latitude', 'longitude', 'geocoded_at'])]
class Address extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'geocoded_at' => 'datetime',
        ];
    }

    public function building(): HasOne
    {
        return $this->hasOne(Building::class);
    }

    public function property(): HasOne
    {
        return $this->hasOne(Property::class);
    }
}
