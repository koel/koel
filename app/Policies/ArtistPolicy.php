<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Facades\License;
use App\Models\Artist;
use App\Models\User;

class ArtistPolicy
{
    public function access(User $user, Artist $artist): bool
    {
        return License::isCommunity() || $artist->belongsToUser($user);
    }

    public function update(User $user, Artist $artist): bool
    {
        if ($artist->is_unknown || $artist->is_various) {
            return false;
        }

        // For CE, if the user can manage songs, they can update any artist.
        if ($user->hasPermissionTo(Permission::MANAGE_SONGS) && License::isCommunity()) {
            return true;
        }

        // For Plus, only the owner of the artist can update it.
        return $artist->belongsToUser($user) && License::isPlus();
    }

    public function edit(User $user, Artist $artist): bool
    {
        return $this->update($user, $artist);
    }
}
