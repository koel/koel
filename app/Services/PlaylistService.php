<?php

namespace App\Services;

use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Facades\License;
use App\Models\Playlist;
use App\Models\PlaylistFolder as Folder;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Values\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

class PlaylistService
{
    public function __construct(private SongRepository $songRepository)
    {
    }

    public function createPlaylist(
        string $name,
        User $user,
        ?Folder $folder = null,
        array $songs = [],
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
        bool $ownSongsOnly = false
    ): Playlist {
        if ($folder) {
            Assert::true($folder->ownedBy($user), 'The playlist folder does not belong to the user');
        }

        throw_if($songs && $ruleGroups, new PlaylistBothSongsAndRulesProvidedException());

        throw_if($ownSongsOnly && (!$ruleGroups || !License::isPlus()), new InvalidArgumentException(
            '"Own songs only" option only works with smart playlists and Plus license.'
        ));

        return DB::transaction(
            static function () use ($name, $user, $songs, $folder, $ruleGroups, $ownSongsOnly): Playlist {
                /** @var Playlist $playlist */
                $playlist = $user->playlists()->create([
                    'name' => $name,
                    'rules' => $ruleGroups,
                    'own_songs_only' => $ownSongsOnly,
                    'folder_id' => $folder?->id,
                ]);

                if (!$playlist->is_smart && $songs) {
                    $playlist->addSongs($songs, $user);
                }

                return $playlist;
            }
        );
    }

    public function updatePlaylist(
        Playlist $playlist,
        string $name,
        ?Folder $folder = null,
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
        bool $ownSongsOnly = false
    ): Playlist {
        if ($folder) {
            Assert::true($playlist->ownedBy($folder->user), 'The playlist folder does not belong to the user');
        }

        throw_if($ownSongsOnly && (!$playlist->is_smart || !License::isPlus()), new InvalidArgumentException(
            '"Own songs only" option only works with smart playlists and Plus license.'
        ));

        $playlist->update([
            'name' => $name,
            'rules' => $ruleGroups,
            'folder_id' => $folder?->id,
            'own_songs_only' => $ownSongsOnly,
        ]);

        return $playlist;
    }

    public function addSongsToPlaylist(Playlist $playlist, Collection|Song|array $songs, User $user): Collection
    {
        return DB::transaction(function () use ($playlist, $songs, $user) {
            $songs = Collection::wrap($songs);
            $playlist->addSongs($songs->filter(static fn ($song): bool => !$playlist->songs->contains($song)), $user);

            // if the playlist is collaborative, make the songs public
            if ($playlist->is_collaborative) {
                $this->makePlaylistSongsPublic($playlist);
            }

            // we want a fresh copy of the songs with the possibly updated visibility
            return $this->songRepository->getManyInCollaborativeContext(ids: $songs->pluck('id')->all(), scopedUser: $user);
        });
    }

    public function removeSongsFromPlaylist(Playlist $playlist, Collection|Song|array $songs): void
    {
        $playlist->removeSongs($songs);
    }

    public function makePlaylistSongsPublic(Playlist $playlist): void
    {
        $playlist->songs()->where('is_public', false)->update(['is_public' => true]);
    }
}
