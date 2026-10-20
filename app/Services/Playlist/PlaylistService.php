<?php

namespace App\Services\Playlist;

use App\Enums\Placement;
use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Facades\License as LicenseFacade;
use App\Models\Playlist;
use App\Models\PlaylistFolder;
use App\Models\Song as Playable;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use App\Services\Image\ImageStorage;
use App\Services\LibrarySummaryService;
use App\Values\Playlist\PlaylistCreateData;
use App\Values\Playlist\PlaylistUpdateData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webmozart\Assert\Assert;

class PlaylistService
{
    public function __construct(
        private readonly ImageStorage $imageStorage,
        private readonly SongRepository $songRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistPlayableMover $playableMover,
        private readonly PlaylistFolderService $folderService,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

    public function createPlaylist(PlaylistCreateData $data, User $user): Playlist
    {
        $cover = $this->imageStorage->storeOptionalImage($data->cover)->fileName;

        return DB::transaction(function () use ($data, $cover, $user): Playlist {
            /** @var Playlist $playlist */
            $playlist = Playlist::query()->create([
                'name' => $data->name,
                'description' => $data->description,
                'rules' => $data->ruleGroups,
                'cover' => $cover,
            ]);

            $user->ownedPlaylists()->attach($playlist, [
                'role' => 'owner',
            ]);

            $folderId = $this->resolveFolderId($data->folderId, $data->folderName, $user);

            if ($folderId) {
                $playlist->folders()->attach($folderId);
            }

            if (!$playlist->is_smart && $data->playableIds) {
                $playlist->addPlayables($data->playableIds, $user);
            }

            return $playlist;
        });
    }

    /** @param array<string, string> $changes */
    public function patchDetails(Playlist $playlist, array $changes): Playlist
    {
        Assert::allInArray(array_keys($changes), ['name', 'description']);

        $playlist->update($changes);

        return $playlist;
    }

    public function updatePlaylist(Playlist $playlist, PlaylistUpdateData $dto): Playlist
    {
        $data = $this->imageStorage->storeOptionalImage($dto->cover)->applyToAttributes([
            'name' => $dto->name,
            'description' => $dto->description,
            'rules' => $dto->ruleGroups,
        ], 'cover');

        $playlist->update($data);

        $folderId = $this->resolveFolderId($dto->folderId, $dto->folderName, $playlist->owner);

        DB::transaction(static function () use ($playlist, $folderId): void {
            // Detach from any folder owned by this user, then attach
            // to the target if one was provided. Covers move-in,
            // move-between, and move-to-root.
            $existingFolderIds = PlaylistFolder::query()
                ->where('user_id', $playlist->owner->id)
                ->whereHas('playlists', static fn (Builder $query) => $query->where('id', $playlist->id))
                ->pluck('id')
                ->all();

            if ($existingFolderIds) {
                $playlist->folders()->detach($existingFolderIds);
            }

            if ($folderId) {
                $playlist->folders()->attach($folderId);
            }
        });

        return $playlist->refresh();
    }

    private function resolveFolderId(?string $folderId, ?string $folderName, User $user): ?string
    {
        if ($folderId) {
            return $folderId;
        }

        if ($folderName) {
            return $this->folderService->createFolder($user, $folderName)->id;
        }

        return null;
    }

    /** @return EloquentCollection<array-key, Playable> */
    public function addPlayablesToPlaylist(
        Playlist $playlist,
        Collection|Playable|array $playables,
        User $user,
    ): EloquentCollection {
        return DB::transaction(function () use ($playlist, $playables, $user) {
            $playables = Collection::wrap($playables);
            $playableIds = $playables->pluck('id')->all();

            $existingIds = $this->playlistRepository
                ->getPlayablePositionsInPlaylist($playlist, $playableIds)
                ->keys()
                ->all();

            $playlist->addPlayables(array_values(array_diff($playableIds, $existingIds)), $user);

            // if the playlist is collaborative, make the songs public
            if ($this->isPlaylistCollaborative($playlist)) {
                $this->makePlaylistContentPublic($playlist, $user);
            }

            // we want a fresh copy of the songs with the possibly updated visibility
            return $this->songRepository->getManyInCollaborativeContext(
                playlist: $playlist,
                ids: $playableIds,
                scopedUser: $user,
            );
        });
    }

    /**
     * @param array<string> $playableIds
     */
    public function replacePlayablesInPlaylist(Playlist $playlist, array $playableIds, User $user): void
    {
        DB::transaction(function () use ($playlist, $playableIds, $user): void {
            $playlist->playables()->detach();
            $playlist->addPlayables(array_values(array_unique($playableIds)), $user);

            if ($this->isPlaylistCollaborative($playlist)) {
                $this->makePlaylistContentPublic($playlist, $user);
            }
        });
    }

    public function removePlayablesFromPlaylist(Playlist $playlist, Collection|Playable|array $playables): void
    {
        $playlist->removePlayables($playables);
    }

    public function makePlaylistContentPublic(Playlist $playlist, User $songOwner): void
    {
        $playlist
            ->playables()
            ->whereBelongsTo($songOwner, 'owner')
            ->where('is_public', false)
            ->update(['is_public' => true]);

        $this->librarySummaryService->markLibraryChanged();
    }

    /** @param array<string> $movingIds */
    public function movePlayablesInPlaylist(
        Playlist $playlist,
        array $movingIds,
        string $target,
        Placement $placement,
    ): void {
        throw_if($playlist->is_smart, OperationNotApplicableForSmartPlaylistException::class);

        $this->playableMover->movePlayables($playlist, $movingIds, $target, $placement);
    }

    public function isPlaylistCollaborative(Playlist $playlist): bool
    {
        return once(
            static fn () => !$playlist->is_smart && LicenseFacade::isPlus() && $playlist->collaborators->isNotEmpty(),
        );
    }

    public function deletePlaylist(Playlist $playlist): void
    {
        $playlist->delete();
    }

    public function deletePlaylistsByUser(User $user): void
    {
        $user
            ->loadMissing('ownedPlaylists')
            ->ownedPlaylists->each(static fn (Playlist $playlist) => $playlist->delete());
    }
}
