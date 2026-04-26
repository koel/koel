<?php

namespace App\Services;

use App\Enums\Acl\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class Acl
{
    /** @return Collection<Role> */
    public function getAssignableRolesForUser(User $user): Collection
    {
        return Role::allAvailable()
            ->filter(static fn (Role $role) => $user->role->canManage($role))
            ->sortBy(static fn (Role $role) => $role->level())
            ->values();
    }
}
