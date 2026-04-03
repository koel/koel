<?php

namespace App\Services;

use App\Models\User;
use App\Models\Song;
use App\Models\DuplicateUpload;
use App\Repositories\DuplicateUploadRepository;
use Illuminate\Contracts\Pagination\Paginator;
use App\Jobs\DeleteSongFilesJob;
use App\Values\Song\SongFileInfo;
use App\Facades\Dispatcher;

class DuplicateUploadService
{
    public function __construct(
        private readonly DuplicateUploadRepository $repository,
        private readonly SongService $songService
    ) {}

    public function findForUser(User $user, int $perPage = 50): Paginator
    {
        return $this->repository->findForUser($user, $perPage);
    }

    public function discardDuplicateUploads(User $user, array $ids): void
    {
        $duplicateUploads = $this->repository->findByIdsForUser($user, $ids);
        $songFiles = $duplicateUploads->map(static fn (DuplicateUpload $upload) => SongFileInfo::make($upload->location, $upload->storage));
        
        Dispatcher::dispatch(new DeleteSongFilesJob($songFiles));
        foreach ($duplicateUploads as $upload) {
            $upload->delete();
        }
    }

    /**
    * @property Carbon $created_at
    * @property Carbon $updated_at
    * @property Song|null $existingSong
    * @property User $user
    * @property string $id
    * @property int $user_id
    * @property string|null $existing_song_id
    * @property string $location
    * @property SongStorageType $storage
    * @property bool $make_public
    * @property bool $extract_folder_structure
    *
    * @method static \Database\Factories\DuplicateUploadFactory factory(...$parameters)
    */

    public function keepDuplicateUploads(User $user, array $ids): void
    {
        $duplicateUploads = $this->repository->findByIdsForUser($user, $ids);
        foreach ($duplicateUploads as $upload) {
            $config = $upload->toScanConfiguration();
            $uploadReference = $upload->toUploadReference();
        }
    }
}
