<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** @extends Repository<Folder> */
class FolderRepository extends Repository
{
    /** @return Collection<Folder>|array<array-key, Folder> */
    private function getOnlyBrowsable(Collection|Folder $folders, ?User $user = null): Collection
    {
        return Collection::wrap($folders)->filter(fn (Folder $folder): bool => $folder->browsableBy( // @phpstan-ignore argument.type
            $user ?? $this->auth->user(),
        ));
    }

    private static function pathToHash(?string $path = null): string
    {
        return simple_hash($path ? trim($path, DIRECTORY_SEPARATOR) : $path);
    }

    /** @return Collection|array<array-key, Folder> */
    public function getSubfolders(?Folder $folder = null, ?User $scopedUser = null): Collection
    {
        if ($folder) {
            return $this->getOnlyBrowsable($folder->subfolders, $scopedUser);
        }

        return $this->getOnlyBrowsable(Folder::query()->whereNull('parent_id')->get(), $scopedUser);
    }

    public function findByPath(?string $path = null): ?Folder
    {
        return $this->findOneBy(['hash' => self::pathToHash($path)]);
    }

    /** @return Collection|array<array-key, Folder> */
    public function getByPaths(array $paths, ?User $scopedUser = null): Collection
    {
        $hashes = array_map(self::pathToHash(...), $paths);

        return $this->getOnlyBrowsable(Folder::query()->whereIn('hash', $hashes)->get(), $scopedUser);
    }
}
