<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Building $building): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $building->isManagedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Building $building): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Building $building): bool
    {
        return $user->hasRole('admin');
    }
}
