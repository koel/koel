<?php

namespace App\Repositories;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** @extends Repository<Playlist> */
class PlaylistRepository extends Repository
{
    /** @return Collection<int, Playlist> */
    public function getAllAccessibleByUser(User $user): Collection
    {
        $accessibleIds = $this->accessibleByUser($user)->pluck('playlists.id');

        return Playlist::query()
            ->whereIn('playlists.id', $accessibleIds)
            ->leftJoin('playlist_playlist_folder', 'playlists.id', '=', 'playlist_playlist_folder.playlist_id')
            ->distinct()
            ->get(['playlists.*', 'playlist_playlist_folder.folder_id']);
    }

    public function searchAccessibleByName(string $name, User $user): ?Playlist
    {
        return $this->accessibleByUser($user)->where('playlists.name', 'like', "%{$name}%")->first();
    }

    private function accessibleByUser(User $user): BelongsToMany
    {
        return License::isCommunity() ? $user->ownedPlaylists() : $user->playlists();
    }
}
