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
        if ($album->is_unknown) {
            return Response::deny();
        }

        if ($user->is_admin) {
            return Response::allow();
        }

        if (License::isCommunity()) {
            return Response::deny('This action is unauthorized.');
        }

        return $user->isCoOwnerOfAlbum($album)
            ? Response::allow()
            : Response::deny('Album is neither owned nor co-owned by the user.');
    }

    public function edit(User $user, Album $album): Response
    {
        return $this->update($user, $album);
    }
}
