<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    public function own(User $user, Song $song): bool
    {
        // Do not use $song->owner->is($user) here, as it may trigger an extra query.
        return $song->owner_id === $user->id;
    }

    public function access(User $user, Song $song): bool
    {
        return License::isCommunity() || $song->accessibleBy($user);
    }

    public function delete(User $user, Song $song): bool
    {
        return (License::isPlus() && $song->ownedBy($user)) || $user->is_admin;
    }

    public function edit(User $user, Song $song): bool
    {
        return (License::isPlus() && $song->accessibleBy($user)) || $user->is_admin;
    }

    public function download(User $user, Song $song): bool
    {
        return config('koel.download.allow') && $this->access($user, $song);
    }
}
