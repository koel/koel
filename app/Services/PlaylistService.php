<?php

namespace App\Services;

use App\Models\Playlist;
use App\Models\User;

class PlaylistService
{
    public function createPlaylist(string $name, User $user, array $songs, ?array $ruleGroups = null): Playlist
    {
        /** @var Playlist $playlist */
        $playlist = $user->playlists()->create([
            'name' => $name,
            'rules' => $ruleGroups,
        ]);

        if (!$playlist->is_smart && $songs) {
            $playlist->songs()->sync($songs);
        }

        return $playlist;
    }

    public function addSongsToPlaylist(Playlist $playlist, array $songIds): void
    {
        $playlist->songs()->syncWithoutDetaching($songIds);
    }

    public function removeSongsFromPlaylist(Playlist $playlist, array $songIds): void
    {
        $playlist->songs()->detach($songIds);
    }
}
