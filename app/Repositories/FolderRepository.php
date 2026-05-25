<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** @extends Repository<Folder> */
class FolderRepository extends Repository
{
    private function getOnlyBrowsable(Collection|Folder $folders, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        if (!$user) {
            return new Collection();
        }

        return Collection::wrap($folders)->filter(static fn (Folder $folder): bool => $folder->browsableBy($user)); // @phpstan-ignore argument.type
    }

    private static function pathToHash(?string $path = null): string
    {
        return simple_hash($path ? trim($path, DIRECTORY_SEPARATOR) : $path);
    }

    /** @return Collection<int, Folder> */
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

    public function findOneByPublicId(?string $publicId = null): ?Folder
    {
        return $publicId ? $this->findOneBy(['id' => $publicId]) : null;
    }

    /** @return Collection<int, Folder> */
    public function getAncestors(Folder $folder): Collection
    {
        $segments = $folder->path ? explode(DIRECTORY_SEPARATOR, trim($folder->path, DIRECTORY_SEPARATOR)) : [];
        array_pop($segments);

        if (!$segments) {
            return new Collection();
        }

        $ancestorPaths = [];

        for ($i = 1, $count = count($segments); $i <= $count; $i++) {
            $ancestorPaths[] = implode(DIRECTORY_SEPARATOR, array_slice($segments, 0, $i));
        }

        $hashes = array_map(self::pathToHash(...), $ancestorPaths);

        $ancestors = Folder::query()->whereIn('hash', $hashes)->get();

        return new Collection(
            $ancestors
                ->sortBy(static fn (Folder $folder): int => array_search($folder->path, $ancestorPaths, true))
                ->values()
                ->all(),
        );
    }

    public function getByPaths(array $paths, ?User $scopedUser = null): Collection
    {
        $hashes = array_map(self::pathToHash(...), $paths);

        return $this->getOnlyBrowsable(Folder::query()->whereIn('hash', $hashes)->get(), $scopedUser);
    }

    /**
     * @param array<int, string> $publicIds
     */
    public function getByPublicIds(array $publicIds, ?User $scopedUser = null): Collection
    {
        return $this->getOnlyBrowsable(Folder::query()->whereIn('id', $publicIds)->get(), $scopedUser);
    }
}
