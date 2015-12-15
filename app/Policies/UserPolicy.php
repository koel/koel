<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function destroy(User $currentUser, User $userToDestroy)
    {
        return $currentUser->is_admin && $currentUser->id !== $userToDestroy->id;
    }
}
