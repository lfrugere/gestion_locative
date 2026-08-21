<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $user->hasRole('admin') || $bankAccount->isManagedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $user->hasRole('admin') || $bankAccount->isManagedBy($user);
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $user->hasRole('admin') || $bankAccount->isManagedBy($user);
    }

    /**
     * Entries (transactions) and reconciliations on the account: open to admin and to a
     * gestionnaire scoped by ownership, mirroring the account-level view/update checks.
     */
    public function manageTransactions(User $user, BankAccount $bankAccount): bool
    {
        return $user->hasRole('admin') || $bankAccount->isManagedBy($user);
    }
}
