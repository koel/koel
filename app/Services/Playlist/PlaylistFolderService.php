<?php

namespace App\Services\Playlist;

use App\Models\Playlist;
use App\Models\PlaylistFolder;
use App\Models\User;
use App\Repositories\PlaylistFolderRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaylistFolderService
{
    public function __construct(
        private readonly PlaylistFolderRepository $repository,
    ) {}

    public function createFolder(User $user, string $name, ?string $parentId = null): PlaylistFolder
    {
        $parent = null;

        if ($parentId) {
            $parent = $this->repository->findOne($parentId);
            self::ensureParentBelongsToUser($parent, $user);
        }

        return $user->playlistFolders()->create([
            'name' => $name,
            'parent_id' => $parent?->id,
        ]);
    }

    public function renameFolder(PlaylistFolder $folder, string $name): PlaylistFolder
    {
        return $this->updateFolder($folder, ['name' => $name]);
    }

    /** @param array{name?: string, parent_id?: ?string} $changes */
    public function updateFolder(PlaylistFolder $folder, array $changes): PlaylistFolder
    {
        if (!array_key_exists('parent_id', $changes)) {
            if (array_key_exists('name', $changes)) {
                $folder->name = $changes['name'];
                $folder->save();
            }

            return $folder;
        }

        if (array_key_exists('name', $changes)) {
            $folder->name = $changes['name'];
        }

        $parentId = $changes['parent_id'];

        if ($parentId === null) {
            $folder->parent_id = null;
        } else {
            $folders = $this->repository->getAllOwnedBy($folder->user);
            $parent = $folders->firstWhere('id', $parentId);
            self::ensureParentBelongsToUser($parent, $folder->user);

            throw_if(
                self::movingFolderWouldCreateCycle($folder, $parent, $folders),
                ValidationException::withMessages([
                    'parent_id' => ['A playlist folder cannot be moved into itself or one of its descendants.'],
                ]),
            );

            $folder->parent_id = $parent->id;
        }

        $folder->save();

        return $folder;
    }

    public function addPlaylistsToFolder(PlaylistFolder $folder, array $playlistIds): void
    {
        DB::transaction(static function () use ($folder, $playlistIds): void {
            // A playlist can only be in one folder by the user at a time
            collect($playlistIds)->each(static function (string $playlistId) use ($folder): void {
                PlaylistFolder::query()
                    ->where('user_id', $folder->user_id)
                    ->whereHas('playlists', static fn (Builder $query) => $query->where('id', $playlistId))
                    ->get()
                    ->each(static fn (PlaylistFolder $folder) => $folder->playlists()->detach($playlistId));
            });

            $folder->playlists()->attach($playlistIds);
        });
    }

    public function movePlaylistsToRootLevel(PlaylistFolder $folder, array $playlistIds): void
    {
        $folder->playlists()->detach($playlistIds);
    }

    public function getFolderForPlaylist(Playlist $playlist, ?User $user = null): ?PlaylistFolder
    {
        return $playlist->folders->firstWhere(static fn (PlaylistFolder $folder) => $folder->user->is(
            $user ?? $playlist->owner,
        ));
    }

    public function deleteFolder(PlaylistFolder $folder): void
    {
        $folder->delete();
    }

    /** @param Collection<int, PlaylistFolder> $folders */
    private static function movingFolderWouldCreateCycle(
        PlaylistFolder $folder,
        PlaylistFolder $newParent,
        Collection $folders,
    ): bool {
        $ancestor = $newParent;

        while ($ancestor) {
            if ($ancestor->is($folder)) {
                return true;
            }

            $ancestor = $ancestor->parent_id === null ? null : $folders->firstWhere('id', $ancestor->parent_id);
        }

        return false;
    }

    private static function ensureParentBelongsToUser(?PlaylistFolder $parent, User $user): void
    {
        throw_unless(
            $parent?->ownedBy($user),
            ValidationException::withMessages([
                'parent_id' => ['The selected parent folder is invalid.'],
            ]),
        );
    }
}
