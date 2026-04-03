<?php

namespace App\Repositories;

use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Models\User;
use App\Services\SongStorages\SongStorage;
use App\Values\Scanning\ScanConfiguration;
use App\Values\UploadReference;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

class DuplicateUploadRepository extends Repository
{
    public function __construct(
        private readonly SongStorage $storage,
    ) {}

    public function create(
        ScanConfiguration $config,
        UploadReference $uploadReference,
        Song $existingSong,
    ): DuplicateUpload {
        return DuplicateUpload::query()->create([
            'user_id' => $config->owner->id,
            'existing_song_id' => $existingSong->id,
            'location' => $uploadReference->location,
            'storage' => $this->storage->getStorageType(),
            'make_public' => $config->makePublic,
            'extract_folder_structure' => $config->extractFolderStructure,
        ]);
    }

    public function findForUser(User $user, int $perPage = 50): Paginator
    {
        return DuplicateUpload::query()
            ->where('user_id', $user->id)
            ->with('existingSong')
            ->simplePaginate($perPage);
    }

    /** @return Collection|array<array-key, DuplicateUpload> */
    public function findByIdsForUser(User $user, array $ids): Collection
    {
        return DuplicateUpload::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->get();
    }
}
