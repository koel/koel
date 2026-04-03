<?php

namespace App\Services;

use App\Models\User;
use App\Models\DuplicateUpload;
use App\Repositories\DuplicateUploadRepository;
use Illuminate\Contracts\Pagination\Paginator;
use App\Jobs\DeleteSongFilesJob;
use App\Values\Song\SongFileInfo;
use App\Facades\Dispatcher;

class DuplicateUploadService
{
    public function __construct(private readonly DuplicateUploadRepository $repository) {}

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

    public function keepDuplicateUploads(User $user, array $ids): void
    {
        $duplicateUploads = $this->repository->findByIdsForUser($user, $ids);
        foreach ($duplicateUploads as $upload) {
            keepDuplicateUpload($upload);
        }
    }
    
    private function keepDuplicateUpload(DuplicateUpload $upload): void
    {

    }
}
