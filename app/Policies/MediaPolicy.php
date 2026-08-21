<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\Property;
use App\Models\PropertyRoom;
use App\Models\Tenant;
use App\Models\User;

class MediaPolicy
{
    // Le rôle admin n'a plus accès aux médias, sur aucune entité, en lecture comme en
    // écriture (cf. docs/roles-permissions.md) : view/update l'excluent explicitement
    // plutôt que de s'appuyer sur les seuls contrôles d'ownership ci-dessous.
    public function view(User $user, Media $media): bool
    {
        if ($user->hasRole('admin')) {
            return false;
        }

        return match ($media->mediable_type) {
            Building::class => $media->mediable->isManagedBy($user),
            Property::class => $user->hasRole('gestionnaire'),
            PropertyRoom::class => $user->hasRole('gestionnaire'),
            Tenant::class => $media->mediable->isManagedBy($user),
            Invoice::class => $this->manageInvoice($user, $media),
            default => false,
        };
    }

    public function update(User $user, Media $media): bool
    {
        if ($user->hasRole('admin')) {
            return false;
        }

        return match ($media->mediable_type) {
            Building::class => false, // 'manage buildings' est réservé à l'admin, exclu ci-dessus.
            Property::class => $this->manageProperty($user, $media->mediable),
            PropertyRoom::class => false, // 'manage properties' est réservé à l'admin, exclu ci-dessus.
            Tenant::class => false, // 'manage tenants' est réservé à l'admin, exclu ci-dessus.
            Invoice::class => $this->manageInvoice($user, $media),
            default => false,
        };
    }

    public function createForBuilding(User $user): bool
    {
        return ! $user->hasRole('admin');
    }

    public function createForProperty(User $user, Property $property): bool
    {
        return $property->isManagedBy($user);
    }

    public function createForPropertyRoom(User $user): bool
    {
        return ! $user->hasRole('admin');
    }

    public function createForTenant(User $user, Tenant $tenant): bool
    {
        return $this->manageTenant($user, $tenant);
    }

    public function createForInvoice(User $user, Property $property): bool
    {
        return $property->isManagedBy($user);
    }

    private function manageProperty(User $user, Property $property): bool
    {
        if ($user->hasRole('admin') || ! $user->hasRole('gestionnaire')) {
            return false;
        }

        return $property->isManagedBy($user);
    }

    private function manageTenant(User $user, Tenant $tenant): bool
    {
        // 'manage tenants' est réservé à l'admin, qui est toujours exclu ici (return false
        // ci-dessus dans les appelants, ou condition ci-dessous) : cette branche est donc
        // toujours fausse, ce qui reproduit le comportement actuel.
        if ($user->hasRole('admin')) {
            return false;
        }

        return false;
    }

    private function manageInvoice(User $user, Media $media): bool
    {
        if ($user->hasRole('admin') || ! $user->hasRole('gestionnaire')) {
            return false;
        }

        $property = $media->mediable->property;

        return $property->isManagedBy($user);
    }
}
