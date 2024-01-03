<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\User;

class UserPolicy
{
    public function admin(User $currentUser): bool
    {
        return $currentUser->is_admin;
    }

    public function destroy(User $currentUser, User $userToDestroy): bool
    {
        return $currentUser->is_admin && $currentUser->isNot($userToDestroy);
    }

    public function upload(User $currentUser): bool
    {
        // For Community Edition, only admins can upload songs.
        // For Plus Edition, any user can upload songs (to their own library).
        return License::isCommunity() ? $currentUser->is_admin : true;
    }
}
