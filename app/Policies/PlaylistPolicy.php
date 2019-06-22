<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function owner(User $user, Playlist $playlist): bool
    {
        return $user->id === $playlist->user_id;
    }
}
