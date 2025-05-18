<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Collection;

/** @extends Repository<Folder> */
class FolderRepository extends Repository
{
    /** @return Collection|array<array-key, Folder> */
    public function getSubfolders(?Folder $folder = null, ?User $user = null): Collection
    {
        if ($folder) {
            return $folder->subfolders;
        }

        // @todo only get folders that are not other users' upload folders?
        return Folder::query()
            ->whereNull('parent_id')
            ->get()
            ->filter(static fn (Folder $folder) => $folder->browsableBy($user ?? auth()->user()));
    }

    public function findByPath(?string $path = null): ?Folder
    {
        return $this->findOneBy(['path' => self::sanitizePath($path)]);
    }

    private static function sanitizePath(?string $path = null): ?string
    {
        return $path ? trim($path, DIRECTORY_SEPARATOR) : $path;
    }
}
