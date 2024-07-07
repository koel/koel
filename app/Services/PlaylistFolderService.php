<?php

namespace App\Services;

use App\Models\PlaylistFolder;
use App\Models\User;

class PlaylistFolderService
{
    public function createFolder(User $user, string $name): PlaylistFolder
    {
        return $user->playlist_folders()->create(['name' => $name]);
    }

    public function renameFolder(PlaylistFolder $folder, string $name): PlaylistFolder
    {
        $folder->update(['name' => $name]);

        return $folder;
    }

    public function addPlaylistsToFolder(PlaylistFolder $folder, array $playlistIds): void
    {
        $folder->playlists()->attach($playlistIds);
    }

    public function movePlaylistsToRootLevel(PlaylistFolder $folder, array $playlistIds): void
    {
        $folder->playlists()->detach($playlistIds);
    }
}
