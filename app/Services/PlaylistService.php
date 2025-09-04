<?php

namespace App\Services;

use App\Enums\Placement;
use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Models\Playlist;
use App\Models\Song as Playable;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Values\PlaylistCreateData;
use App\Values\PlaylistUpdateData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlaylistService
{
    public function __construct(private readonly SongRepository $songRepository)
    {
    }

    public function createPlaylist(PlaylistCreateData $data, User $user): Playlist
    {
        return DB::transaction(
            static function () use ($data, $user): Playlist {
                /** @var Playlist $playlist */
                $playlist = Playlist::query()->create([
                    'name' => $data->name,
                    'description' => $data->description,
                    'rules' => $data->ruleGroups,
                ]);

                $user->ownedPlaylists()->attach($playlist, [
                    'role' => 'owner',
                ]);

                $playlist->folders()->attach($data->folderId);

                if (!$playlist->is_smart && $data->playableIds) {
                    $playlist->addPlayables($data->playableIds, $user);
                }

                return $playlist;
            }
        );
    }

    public function updatePlaylist(Playlist $playlist, PlaylistUpdateData $data): Playlist
    {
        $playlist->update([
            'name' => $data->name,
            'description' => $data->description,
            'rules' => $data->ruleGroups,
        ]);

        if ($data->folderId) {
            $playlist->folders()->syncWithoutDetaching([$data->folderId]);
        }

        return $playlist;
    }

    /** @return EloquentCollection<array-key, Playable> */
    public function addPlayablesToPlaylist(
        Playlist $playlist,
        Collection|Playable|array $playables,
        User $user
    ): EloquentCollection {
        return DB::transaction(function () use ($playlist, $playables, $user) {
            $playables = Collection::wrap($playables);

            $playlist->addPlayables(
                $playables->filter(static fn ($song): bool => !$playlist->playables->contains($song)),
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
        $playlist->playables()->where('is_public', false)->update(['is_public' => true]);
    }

    /** @param array<string> $movingIds */
    public function movePlayablesInPlaylist(
        Playlist $playlist,
        array $movingIds,
        string $target,
        Placement $placement,
    ): void {
        throw_if($playlist->is_smart, OperationNotApplicableForSmartPlaylistException::class);

        DB::transaction(static function () use ($playlist, $movingIds, $target, $placement): void {
            $targetPosition = $playlist->playables()->wherePivot('song_id', $target)->value('position');
            $insertPosition = $placement === Placement::BEFORE ? $targetPosition : $targetPosition + 1;

            // create a "gap" for the moving songs by incrementing the position of the songs after the target
            $playlist->playables()
                ->newPivotQuery()
                ->where('position', $placement === Placement::BEFORE ? '>=' : '>', $targetPosition)
                ->whereNotIn('song_id', $movingIds)
                ->increment('position', count($movingIds));

            $values = [];

            foreach ($movingIds as $id) {
                $values[$id] = ['position' => $insertPosition++];
            }

            $playlist->playables()->syncWithoutDetaching($values);
        });
    }

    public function deleteCover(Playlist $playlist): void
    {
        $playlist->cover = null;
        $playlist->save(); // will trigger cover cleanup in Playlist Observer
    }
}
