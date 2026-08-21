<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    // Ces contrôles reposent uniquement sur l'ownership (isManagedBy) de l'entité notée,
    // qui est par construction toujours faux pour un admin (cf. docs/roles-permissions.md).
    public function create(User $user, object $notable): bool
    {
        return $notable->isManagedBy($user);
    }

    public function update(User $user, Note $note): bool
    {
        if (! ($user->hasRole('admin') || $user->hasRole('gestionnaire'))) {
            return false;
        }

        return $note->created_by === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->update($user, $note);
    }
}
