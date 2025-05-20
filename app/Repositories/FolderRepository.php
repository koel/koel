<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** @extends Repository<Folder> */
class FolderRepository extends Repository
{
    /** @return Collection<Folder> */
    private static function getOnlyBrowsable(Collection $folders, ?User $user = null): Collection
    {
        return $folders->filter(static fn (Folder $folder) => $folder->browsableBy($user ?? auth()->user())); // @phpstan-ignore-line
    }

    private static function sanitizePath(?string $path = null): ?string
    {
        return $path ? trim($path, DIRECTORY_SEPARATOR) : $path;
    }

    /** @return Collection|array<array-key, Folder> */
    public function getSubfolders(?Folder $folder = null, ?User $scopedUser = null): Collection
    {
        if ($folder) {
            return $folder->subfolders;
        }

        return Folder::query()
            ->whereNull('parent_id')
            ->get()
            ->filter(static fn (Collection|Folder $folders) => self::getOnlyBrowsable( // @phpstan-ignore-line
                Collection::wrap($folders),
                $scopedUser
            ));
    }

    public function findByPath(?string $path = null): ?Folder
    {
        return $this->findOneBy(['path' => self::sanitizePath($path)]);
    }

    /** @return Collection<Folder> */
    public function getByPaths(array $paths, ?User $scopedUser = null): Collection
    {
        return Folder::query()
            ->whereIn('path', $paths)
            ->get()
            ->filter(static fn (Collection|Folder $folders) => self::getOnlyBrowsable( // @phpstan-ignore-line
                Collection::wrap($folders),
                $scopedUser
            ));
    }
}
