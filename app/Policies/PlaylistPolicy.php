<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function own(User $user, Playlist $playlist): bool
    {
        return $playlist->user->is($user);
    }

    public function download(User $user, Playlist $playlist): bool
    {
        return $this->own($user, $playlist);
    }
}
