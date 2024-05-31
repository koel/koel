<?php

namespace App\Services;

use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Facades\License;
use App\Models\Playlist;
use App\Models\PlaylistFolder as Folder;
use App\Models\Song as Playable;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Values\SmartPlaylistRuleGroupCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

class PlaylistService
{
    public function __construct(private readonly SongRepository $songRepository)
    {
    }

    public function createPlaylist(
        string $name,
        User $user,
        ?Folder $folder = null,
        array $playables = [],
        ?SmartPlaylistRuleGroupCollection $ruleGroups = null,
        bool $ownSongsOnly = false
    ): Playlist {
        if ($folder) {
            Assert::true($folder->ownedBy($user), 'The playlist folder does not belong to the user');
        }

        throw_if($playables && $ruleGroups, new PlaylistBothSongsAndRulesProvidedException());

        throw_if($ownSongsOnly && (!$ruleGroups || !License::isPlus()), new InvalidArgumentException(
            '"Own songs only" option only works with smart playlists and Plus license.'
        ));

        return DB::transaction(
            static function () use ($name, $user, $playables, $folder, $ruleGroups, $ownSongsOnly): Playlist {
                $playlist = $user->playlists()->create([
                    'name' => $name,
                    'rules' => $ruleGroups,
                    'own_songs_only' => $ownSongsOnly,
                ]);

                $folder?->playlists()->attach($playlist);

                if (!$playlist->is_smart && $playables) {
                    $playlist->addPlayables($playables, $user);
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
            'own_songs_only' => $ownSongsOnly,
        ]);

        $folder?->playlists()->syncWithoutDetaching($playlist);

        return $playlist;
    }

    /** @return Collection<array-key, Playable> */
    public function addPlayablesToPlaylist(
        Playlist $playlist,
        Collection|Playable|array $playables,
        User $user
    ): Collection {
        return DB::transaction(function () use ($playlist, $playables, $user) {
            $playables = Collection::wrap($playables);

            $playlist->addPlayables(
                $playables->filter(static fn ($song): bool => !$playlist->songs->contains($song)),
                $user
            );

            // if the playlist is collaborative, make the songs public
            if ($playlist->is_collaborative) {
                $this->makePlaylistContentPublic($playlist);
            }

            // we want a fresh copy of the songs with the possibly updated visibility
            return $this->songRepository->getManyInCollaborativeContext(
                ids: $playables->pluck('id')->all(),
                scopedUser: $user
            );
        });
    }

    public function removePlayablesFromPlaylist(Playlist $playlist, Collection|Playable|array $playables): void
    {
        $playlist->removePlayables($playables);
    }

    public function makePlaylistContentPublic(Playlist $playlist): void
    {
        $playlist->songs()->where('is_public', false)->update(['is_public' => true]);
    }

    public function movePlayablesInPlaylist(Playlist $playlist, array $movingIds, string $target, string $type): void
    {
        Assert::oneOf($type, ['before', 'after']);
        throw_if($playlist->is_smart, OperationNotApplicableForSmartPlaylistException::class);

        DB::transaction(static function () use ($playlist, $movingIds, $target, $type): void {
            $targetPosition = $playlist->songs()->wherePivot('song_id', $target)->value('position');
            $insertPosition = $type === 'before' ? $targetPosition : $targetPosition + 1;

            // create a "gap" for the moving songs by incrementing the position of the songs after the target
            $playlist->songs()
                ->newPivotQuery()
                ->where('position', $type === 'before' ? '>=' : '>', $targetPosition)
                ->whereNotIn('song_id', $movingIds)
                ->increment('position', count($movingIds));

            $values = [];

            foreach ($movingIds as $id) {
                $values[$id] = ['position' => $insertPosition++];
            }

            $playlist->songs()->syncWithoutDetaching($values);
        });
    }
}
