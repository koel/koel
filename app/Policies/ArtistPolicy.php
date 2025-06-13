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

        // For CE, if the user is an admin, they can update any artist.
        if ($user->is_admin && License::isCommunity()) {
            return Response::allow();
        }

        // For Plus, only the owner of the artist can update it.
        if ($artist->belongsToUser($user) && License::isPlus()) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function edit(User $user, Artist $artist): Response
    {
        return $this->update($user, $artist);
    }
}
