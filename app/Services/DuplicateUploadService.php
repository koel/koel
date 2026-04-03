<?php

namespace App\Services;

use App\Models\User;
use App\Models\DuplicateUpload;
use App\Repositories\DuplicateUploadRepository;
use Illuminate\Contracts\Pagination\Paginator;
use App\Jobs\DeleteSongFilesJob;
use App\Values\Song\SongFileInfo;
use App\Services\Scanners\FileScanner;
use App\Facades\Dispatcher;
use App\Exceptions\SongUploadFailedException;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use Illuminate\Support\Facades\File;
use Throwable;

class DuplicateUploadService
{
    public function __construct(
        private readonly DuplicateUploadRepository $repository,
        private readonly FileScanner $scanner,
        private readonly SongService $songService,
        private readonly SongStorage $storage
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

    public function keepDuplicateUploads(User $user, array $ids): void
    {
        $duplicateUploads = $this->repository->findByIdsForUser($user, $ids);
        foreach ($duplicateUploads as $upload) {
            $config = $upload->toScanConfiguration();
            $localFilePath = $this->storage->getLocalPath($upload->location);

            try {
                $song = $this->songService->createOrUpdateSongFromScan(
                    $this->scanner->scan($localFilePath),
                    $config,
                );
            } catch (Throwable $error) {
                throw SongUploadFailedException::make($error);
            } finally {
                if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
                    File::delete($localFilePath);
                }
            }

            // Since we scanned a local file, the song's path was initially set to the local path.
            // We need to update it to the actual storage (e.g. S3) and location (e.g., the S3 key) if applicable.
            if ($song->path !== $upload->location || $song->storage !== $this->storage->getStorageType()) {
                $song->update([
                    'path' => $upload->location,
                    'storage' => $this->storage->getStorageType(),
                ]);
            }
            $upload->delete();
        }
    }
}
