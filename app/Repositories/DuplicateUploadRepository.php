<?php

namespace App\Repositories;

use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Models\User;
use App\Services\SongStorages\SongStorage;
use App\Values\Scanning\ScanConfiguration;
use App\Values\UploadReference;
use Illuminate\Database\Eloquent\Collection;

class DuplicateUploadRepository extends Repository
{
    public function __construct(private readonly SongStorage $storage) {}

    public function create(ScanConfiguration $config, UploadReference $uploadReference, Song $existingSong): DuplicateUpload
    {
        return DuplicateUpload::query()->create([
            'user_id' => $config->owner->id,
            'existing_song_id' => $existingSong->id,
            'location' => $uploadReference->location,
            'storage' => $this->storage->getStorageType(),
            'make_public' => $config->makePublic,
            'extract_folder_structure' => $config->extractFolderStructure,
        ]);
    }

    public function findForUser(User $user): Collection
    {
        return DuplicateUpload::query()->where('user_id', $user->id)->get();
    }

    public function deleteExpired(int $ttlHours = 24): void
    {
        DuplicateUpload::query()
            ->where('created_at', '<', now()->subHours($ttlHours))
            ->each(function (DuplicateUpload $record): void {
                $this->storage->delete($record->location);
                $record->delete();
            });
    }
}
