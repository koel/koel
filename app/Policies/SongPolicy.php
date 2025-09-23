<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Facades\License;
use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    public function access(User $user, Song $song): bool
    {
        return License::isCommunity() || $song->accessibleBy($user);
    }

    public function own(User $user, Song $song): bool
    {
        return $song->ownedBy($user);
    }

    public function delete(User $user, Song $song): bool
    {
        return License::isCommunity()
            ? $user->hasPermissionTo(Permission::MANAGE_SONGS)
            : $song->ownedBy($user);
    }

    public function edit(User $user, Song $song): bool
    {
        return License::isCommunity()
            ? $user->hasPermissionTo(Permission::MANAGE_SONGS)
            : $song->accessibleBy($user);
    }

    public function download(User $user, Song $song): bool
    {
        return $this->access($user, $song);
    }
}
