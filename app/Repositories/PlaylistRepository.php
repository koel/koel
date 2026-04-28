<?php

namespace App\Repositories;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/** @extends Repository<Playlist> */
class PlaylistRepository extends Repository
{
    public function getAllAccessibleByUser(User $user): Collection
    {
        return $this
            ->accessibleByUser($user)
            ->leftJoin('playlist_playlist_folder', 'playlists.id', '=', 'playlist_playlist_folder.playlist_id')
            ->distinct()
            ->get(['playlists.*', 'playlist_playlist_folder.folder_id']);
    }

    public function searchAccessibleByName(string $name, User $user): ?Playlist
    {
        /** @var ?Playlist $playlist */
        $playlist = $this->accessibleByUser($user)->where('playlists.name', 'like', "%{$name}%")->first();

        return $playlist;
    }

    private static function accessibleByUser(User $user): BelongsToMany
    {
        return License::isCommunity() ? $user->ownedPlaylists() : $user->playlists();
    }
}
