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
        return DB::transaction(function () use ($user, $name, $parentId): PlaylistFolder {
            $parent = null;

            if ($parentId) {
                $parent = $this->repository->findOneForUpdate($parentId);
                self::ensureParentBelongsToUser($parent, $user);
            }

            return $user->playlistFolders()->create([
                'name' => $name,
                'parent_id' => $parent?->id,
            ]);
        }, attempts: 5);
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

        return DB::transaction(function () use ($folder, $changes): PlaylistFolder {
            $folders = $this->repository->getAllOwnedByForUpdate($folder->user);
            $lockedFolder = $folders->firstWhere('id', $folder->id);

            throw_unless($lockedFolder, ValidationException::withMessages([
                'parent_id' => ['The playlist folder no longer exists.'],
            ]));

            if (array_key_exists('name', $changes)) {
                $lockedFolder->name = $changes['name'];
            }

            $parentId = $changes['parent_id'];

            if ($parentId === null) {
                $lockedFolder->parent_id = null;
            } else {
                $parent = $folders->firstWhere('id', $parentId);
                self::ensureParentBelongsToUser($parent, $lockedFolder->user);

                throw_if(
                    self::movingFolderWouldCreateCycle($lockedFolder, $parent, $folders),
                    ValidationException::withMessages([
                        'parent_id' => ['A playlist folder cannot be moved into itself or one of its descendants.'],
                    ]),
                );

                $lockedFolder->parent_id = $parent->id;
            }

            $lockedFolder->save();

            return $lockedFolder;
        }, attempts: 5);
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
