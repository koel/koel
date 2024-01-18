<?php

namespace App\Repositories;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Support\Collection;

class PlaylistRepository extends Repository
{
    /** @return array<array-key, Playlist>|Collection<Playlist> */
    public function getAllAccessibleByUser(User $user): Collection
    {
        if (License::isCommunity()) {
            return $user->playlists;
        }

        return $user->playlists->merge($user->collaboratedPlaylists);
    }
}
