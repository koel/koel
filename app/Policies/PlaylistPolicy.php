<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function access(User $user, Playlist $playlist): bool
    {
        return $this->own($user, $playlist) || $playlist->hasCollaborator($user);
    }

    public function own(User $user, Playlist $playlist): bool
    {
        return $playlist->ownedBy($user);
    }

    public function download(User $user, Playlist $playlist): bool
    {
        return $this->access($user, $playlist);
    }

    public function inviteCollaborators(User $user, Playlist $playlist): bool
    {
        return License::isPlus() && $this->own($user, $playlist) && !$playlist->is_smart;
    }

    public function collaborate(User $user, Playlist $playlist): bool
    {
        return $this->own($user, $playlist) || $playlist->hasCollaborator($user);
    }
}
