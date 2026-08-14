<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['line1', 'line2', 'postal_code', 'city', 'country'])]
class Address extends Model
{
    public function building(): HasOne
    {
        return $this->hasOne(Building::class);
    }

    public function property(): HasOne
    {
        return $this->hasOne(Property::class);
    }
}
