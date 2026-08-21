<?php

namespace App\Policies;

use App\Models\PropertyRoom;
use App\Models\User;

class PropertyRoomPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, PropertyRoom $room): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, PropertyRoom $room): bool
    {
        return $user->hasRole('admin');
    }
}
