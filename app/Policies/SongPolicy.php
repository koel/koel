<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    public function own(User $user, Song $song): bool
    {
        return $song->owner_id === $user->id;
    }

    public function play(User $user, Song $song): bool
    {
        return License::isCommunity() || $song->is_public || $song->owner_id === $user->id;
    }

    public function interact(User $user, Song $song): bool
    {
        return License::isCommunity() || $song->is_public || $song->owner_id === $user->id;
    }

    public function delete(User $user, Song $song): bool
    {
        return (License::isCommunity() && $user->is_admin) || $song->owner_id === $user->id;
    }

    public function edit(User $user, Song $song): bool
    {
        return (License::isCommunity() && $user->is_admin) || $song->owner_id === $user->id;
    }

    public function download(User $user, Song $song): bool
    {
        if (!config('koel.download.allow')) {
            return false;
        }

        return License::isCommunity() || $song->is_public || $song->owner_id === $user->id;
    }
}
