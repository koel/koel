<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function destroy(User $currentUser, User $userToDestroy)
    {
        return $currentUser->is_admin && $currentUser->id !== $userToDestroy->id;
    }
}
