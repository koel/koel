<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function own(User $user, Playlist $playlist): bool
    {
        return $playlist->ownedBy($user);
    }

    public function download(User $user, Playlist $playlist): bool
    {
        return $this->own($user, $playlist);
    }

    public function inviteCollaborators(User $user, Playlist $playlist): bool
    {
        return $this->own($user, $playlist) && !$playlist->is_smart;
    }

    public function collaborate(User $user, Playlist $playlist): bool
    {
        if ($this->own($user, $playlist)) {
            return true;
        }

        return $playlist->hasCollaborator($user);
    }
}
