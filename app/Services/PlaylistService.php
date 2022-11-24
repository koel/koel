<?php

namespace App\Services;

use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Models\Playlist;
use App\Models\PlaylistFolder as Folder;
use App\Models\User;
use App\Values\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Facades\DB;
use Webmozart\Assert\Assert;

class PlaylistService
{
    public function createPlaylist(
        string $name,
        User $user,
        ?Folder $folder = null,
        array $songs = [],
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null
    ): Playlist {
        if ($folder) {
            Assert::true($user->is($folder->user), 'The playlist folder does not belong to the user');
        }

        if ($songs && $ruleGroups) {
            throw new PlaylistBothSongsAndRulesProvidedException();
        }

        return DB::transaction(static function () use ($name, $user, $songs, $folder, $ruleGroups): Playlist {
            /** @var Playlist $playlist */
            $playlist = $user->playlists()->create([
                'name' => $name,
                'rules' => $ruleGroups,
            ]);

            $folder?->playlists()->save($playlist);

            if (!$playlist->is_smart && $songs) {
                $playlist->songs()->sync($songs);
            }

            return $playlist;
        });
    }

    public function updatePlaylist(
        Playlist $playlist,
        string $name,
        ?Folder $folder = null,
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
    ): Playlist {
        if ($folder) {
            Assert::true($playlist->user->is($folder->user), 'The playlist folder does not belong to the user');
        }

        $playlist->update([
            'name' => $name,
            'rules' => $ruleGroups,
            'folder_id' => $folder?->id,
        ]);

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

    /** @deprecated since v6.0.0, use add/removeSongs methods instead */
    public function populatePlaylist(Playlist $playlist, array $songIds): void
    {
        $playlist->songs()->sync($songIds);
    }
}
