<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\User;

class LicensePolicy
{
    public function activate(User $user): bool
    {
        return $user->is_admin && License::isCommunity();
    }
}
