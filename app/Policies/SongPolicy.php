<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;

class SongPolicy
{
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
}
