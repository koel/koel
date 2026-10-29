<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserFavorites;

class UserFavoritesPolicy
{
    public function embed(User $user, UserFavorites $favorites): bool
    {
        return !$user->isGuest() && $favorites->public_id === $user->public_id;
    }
}
