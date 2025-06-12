<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** @extends Repository<Folder> */
class FolderRepository extends Repository
{
    /** @return Collection<Folder>|array<array-key, Folder> */
    private static function getOnlyBrowsable(Collection|Folder $folders, ?User $user = null): Collection
    {
        return Collection::wrap($folders)
            ->filter(static fn (Folder $folder) => $folder->browsableBy($user ?? auth()->user())); // @phpstan-ignore-line
    }

    private static function pathToHash(?string $path = null): string
    {
        return simple_hash($path ? trim($path, DIRECTORY_SEPARATOR) : $path);
    }

    /** @return Collection|array<array-key, Folder> */
    public function getSubfolders(?Folder $folder = null, ?User $scopedUser = null): Collection
    {
        if ($folder) {
            return $folder->subfolders;
        }

        return self::getOnlyBrowsable(
            Folder::query()->whereNull('parent_id')->get(),
            $scopedUser
        );
    }

    public function findByPath(?string $path = null): ?Folder
    {
        return $this->findOneBy(['hash' => self::pathToHash($path)]);
    }

    /** @return Collection|array<array-key, Folder> */
    public function getByPaths(array $paths, ?User $scopedUser = null): Collection
    {
        $hashes = array_map(self::pathToHash(...), $paths);

        return self::getOnlyBrowsable(
            Folder::query()->whereIn('hash', $hashes)->get(),
            $scopedUser
        );
    }
}
