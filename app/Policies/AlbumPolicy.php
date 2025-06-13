<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Album;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AlbumPolicy
{
    /**
     * If the user can update the album (e.g., edit name, year, or upload the cover image).
     */
    public function update(User $user, Album $album): Response
    {
        // Unknown albums are not editable.
        if ($album->is_unknown) {
            return Response::deny();
        }

        // For CE, if the user is an admin, they can update any album.
        if ($user->is_admin && License::isCommunity()) {
            return Response::allow();
        }

        // For Plus, only the owner of the album can update it.
        if ($album->belongsToUser($user) && License::isPlus()) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function edit(User $user, Album $album): Response
    {
        return $this->update($user, $album);
    }
}
