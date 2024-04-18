<?php

namespace App\Repositories;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Support\Collection;

class PlaylistRepository extends Repository
{
    /** @return Collection<array-key, Playlist> */
    public function getAllAccessibleByUser(User $user): Collection
    {
        $ownPlaylists = Playlist::query()
            ->where('playlists.user_id', $user->id)
            ->leftJoin('playlist_playlist_folder', 'playlists.id', '=', 'playlist_playlist_folder.playlist_id')
            ->get(['playlists.*', 'playlist_playlist_folder.folder_id']);

        if (License::isCommunity()) {
            return $ownPlaylists;
        }

        $collaboratedPlaylists = Playlist::query()
            ->join('playlist_collaborators', 'playlists.id', '=', 'playlist_collaborators.playlist_id')
            ->where('playlist_collaborators.user_id', $user->id)
            ->join('playlist_playlist_folder', 'playlists.id', '=', 'playlist_playlist_folder.playlist_id')
            ->get(['playlists.*', 'playlist_playlist_folder.folder_id']);

        return $ownPlaylists->merge($collaboratedPlaylists);
    }
}
