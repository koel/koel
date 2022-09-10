<?php

namespace App\Services;

use App\Models\Playlist;
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
        Playlist::query()->whereIn('id', $playlistIds)->update(['folder_id' => $folder->id]);
    }

    public function movePlaylistsToRootLevel(array $playlistIds): void
    {
        Playlist::query()->whereIn('id', $playlistIds)->update(['folder_id' => null]);
    }
}
