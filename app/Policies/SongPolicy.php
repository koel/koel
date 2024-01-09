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

    public function access(User $user, Song $song): bool
    {
        return License::isCommunity() || $song->accessibleBy($user);
    }

    public function delete(User $user, Song $song): bool
    {
        return (License::isPlus() && $song->accessibleBy($user)) || $user->is_admin;
    }

    public function edit(User $user, Song $song): bool
    {
        return (License::isPlus() && $song->accessibleBy($user)) || $user->is_admin;
    }

    public function download(User $user, Song $song): bool
    {
        if (!config('koel.download.allow')) {
            return false;
        }

        return $this->access($user, $song);
    }
}
