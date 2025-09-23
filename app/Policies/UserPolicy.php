<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Facades\License;
use App\Models\User;

class UserPolicy
{
    public function manage(User $currentUser): bool
    {
        return $currentUser->hasPermissionTo(Permission::MANAGE_USERS);
    }

    public function update(User $currentUser, User $userToUpdate): bool
    {
        return $currentUser->hasPermissionTo(Permission::MANAGE_USERS)
            && $currentUser->canManage($userToUpdate);
    }

    public function destroy(User $currentUser, User $userToDestroy): bool
    {
        return $currentUser->hasPermissionTo(Permission::MANAGE_USERS)
            && $userToDestroy->isNot($currentUser)
            && $currentUser->canManage($userToDestroy);
    }

    public function upload(User $currentUser): bool
    {
        return License::isCommunity()
            ? $currentUser->hasPermissionTo(Permission::MANAGE_SONGS)
            : true; // For Plus Edition, any user can upload songs (to their own library).
    }
}
