<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Facades\License;
use App\Models\User;

class LicensePolicy
{
    public function activate(User $user): bool
    {
        return $user->hasPermissionTo(Permission::MANAGE_SETTINGS) && License::isCommunity();
    }
}
