<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[Fillable(['civility', 'last_name', 'first_name', 'birth_date', 'status', 'status_changed_at'])]
class Tenant extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            $tenant->storage_key ??= (string) Str::ulid();
        });
    }

    public const CIVILITY_MR = 'mr';

    public const CIVILITY_MRS = 'mrs';

    public const CIVILITY_OTHER = 'other';

    public const CIVILITY_LABELS = [
        self::CIVILITY_MR => 'Monsieur',
        self::CIVILITY_MRS => 'Madame',
        self::CIVILITY_OTHER => 'Autre / non précisée',
    ];

    public const STATUS_CANDIDATE = 'candidate';

    public const STATUS_VALIDATING = 'validating';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FORMER = 'former';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_LABELS = [
        self::STATUS_CANDIDATE => 'Candidat',
        self::STATUS_VALIDATING => 'En validation',
        self::STATUS_ACTIVE => 'Actif',
        self::STATUS_FORMER => 'Ancien',
        self::STATUS_REFUSED => 'Refusé',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'status_changed_at' => 'datetime',
        ];
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function civilityLabel(): string
    {
        return self::CIVILITY_LABELS[$this->civility] ?? $this->civility;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
