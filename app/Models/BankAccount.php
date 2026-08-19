<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'iban', 'country', 'initial_balance', 'initial_balance_date', 'balance', 'manager_id'])]
class BankAccount extends Model
{
    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'initial_balance_date' => 'date',
            'balance' => 'decimal:2',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class)->latest('date');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class)->latest('statement_date');
    }
}
