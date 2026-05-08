<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Enums\Acl\Role;
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
        return (
            $currentUser->hasPermissionTo(Permission::MANAGE_USERS)
            && $currentUser->role->canManage($userToUpdate->role)
        );
    }

    public function destroy(User $currentUser, User $userToDestroy): bool
    {
        return (
            $currentUser->hasPermissionTo(Permission::MANAGE_USERS)
            && $userToDestroy->isNot($currentUser)
            && $currentUser->role->canManage($userToDestroy->role)
        );
    }

    public function upload(User $currentUser): bool
    {
        if (License::isCommunity()) {
            return $currentUser->hasPermissionTo(Permission::MANAGE_SONGS);
        }

        // On Plus, every authenticated user can upload to their own library — except Guests,
        // who have no library by design.
        return $currentUser->role !== Role::GUEST;
    }
}
