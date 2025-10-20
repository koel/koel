<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;

class ThemePolicy
{
    public function own(User $user, Theme $theme): bool
    {
        return $user->id === $theme->user_id;
    }
}
