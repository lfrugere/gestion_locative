<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;

class InvoicePolicy
{
    // Le rôle admin n'a plus accès aux factures (cf. docs/roles-permissions.md) : seul
    // l'ownership (isManagedBy) autorise l'accès, ce qui exclut de facto l'admin puisqu'il
    // n'est jamais rattaché comme gestionnaire.
    public function create(User $user, Property $property): bool
    {
        return $property->isManagedBy($user);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $invoice->property->isManagedBy($user);
    }
}
