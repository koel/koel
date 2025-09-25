<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Facades\License;
use App\Models\Album;
use App\Models\User;

class AlbumPolicy
{
    public function access(User $user, Album $album): bool
    {
        return License::isCommunity() || $album->belongsToUser($user);
    }

    /**
     * If the user can update the album (e.g., edit name, year, or upload the cover image).
     */
    public function update(User $user, Album $album): bool
    {
        // Unknown albums are not editable.
        if ($album->is_unknown) {
            return false;
        }

        // For CE, if the user can manage songs, they can update any album.
        if ($user->hasPermissionTo(Permission::MANAGE_SONGS) && License::isCommunity()) {
            return true;
        }

        // For Plus, only the owner of the album can update it.
        return $album->belongsToUser($user) && License::isPlus();
    }

    public function edit(User $user, Album $album): bool
    {
        return $this->update($user, $album);
    }
}
