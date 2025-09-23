<?php

namespace App\Services;

use App\Models\PlaylistFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PlaylistFolderService
{
    public function createFolder(User $user, string $name): PlaylistFolder
    {
        return $user->playlistFolders()->create(['name' => $name]);
    }

    public function renameFolder(PlaylistFolder $folder, string $name): PlaylistFolder
    {
        $folder->update(['name' => $name]);

        return $folder;
    }

    public function addPlaylistsToFolder(PlaylistFolder $folder, array $playlistIds): void
    {
        DB::transaction(
            static function () use ($folder, $playlistIds): void {
                // A playlist can only be in one folder by the user at a time
                collect($playlistIds)->each(
                    static function (string $playlistId) use ($folder): void {
                        PlaylistFolder::query()
                            ->where('user_id', $folder->user_id)
                            ->whereHas('playlists', static fn (Builder $query) => $query->where('id', $playlistId))
                            ->get()
                            ->each(static fn (PlaylistFolder $folder) => $folder->playlists()->detach($playlistId));
                    }
                );

                $folder->playlists()->attach($playlistIds);
            }
        );
    }

    public function movePlaylistsToRootLevel(PlaylistFolder $folder, array $playlistIds): void
    {
        $folder->playlists()->detach($playlistIds);
    }
}
