<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ArtistPolicy
{
    public function update(User $user, Artist $artist): Response
    {
        if ($artist->is_unknown || $artist->is_various) {
            return Response::deny();
        }

        if ($user->is_admin) {
            return Response::allow();
        }

        if (License::isCommunity()) {
            return Response::deny('This action is unauthorized.');
        }

        return $user->isCoOwnerOfArtist($artist)
            ? Response::allow()
            : Response::deny('Artist is neither owned nor co-owned by the user.');
    }

    public function edit(User $user, Artist $artist): Response
    {
        return $this->update($user, $artist);
    }
}
