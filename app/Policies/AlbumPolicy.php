<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AlbumPolicy
{
    /**
     * If the user can update the album (e.g. upload cover).
     */
    public function update(User $user, Album $album): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        if (License::isCommunity()) {
            return Response::deny('This action is unauthorized.');
        }

        return $album->songs->every(static fn (Song $song) => $song->ownedBy($user))
            ? Response::allow()
            : Response::deny('Album is not owned by the user.');
    }
}
