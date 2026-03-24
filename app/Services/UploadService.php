<?php

namespace App\Services;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Repositories\DuplicateUploadRepository;
use App\Repositories\SongRepository;
use App\Services\Scanners\FileScanner;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Values\Scanning\ScanConfiguration;
use App\Values\UploadReference;
use Illuminate\Support\Facades\File;
use Throwable;

class UploadService
{
    public function __construct(
        private readonly SongService $songService,
        private readonly SongStorage $storage,
        private readonly FileScanner $scanner,
        private readonly SongRepository $songRepository,
        private readonly DuplicateUploadRepository $duplicateUploadRepository,
    ) {}

    public function handleUpload(string $filePath, User $uploader): Song
    {
        $hash = File::hash($filePath);
        $existingSong = $this->songRepository->findByHash($hash, $uploader);

        if ($existingSong) {
            // TODO: check to make sure that after the upload of the file it is still safe and doesn't need to be moved into some extra "quarrentine area"
            // It seems like we might need to call storeUploadedFile to move it to 'quarrentine'
            $this->duplicateUploadRepository->create($uploader, $filePath, $existingSong);
            throw DuplicateSongUploadException::fromFilePath($filePath);
        }

        $uploadReference = $this->storage->storeUploadedFile($filePath, $uploader);

        $config = ScanConfiguration::make(
            owner: $uploader,
            makePublic: $uploader->preferences->makeUploadsPublic,
            extractFolderStructure: $this->storage->getStorageType()->supportsFolderStructureExtraction(),
        );

        try {
            $song = $this->songService->createOrUpdateSongFromScan(
                $this->scanner->scan($uploadReference->localPath),
                $config,
            );
        } catch (Throwable $error) {
            $this->handleUploadFailure($uploadReference, $error);
        }

        if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
            File::delete($uploadReference->localPath);
        }

        // Since we scanned a local file, the song's path was initially set to the local path.
        // We need to update it to the actual storage (e.g. S3) and location (e.g., the S3 key) if applicable.
        if ($song->path !== $uploadReference->location || $song->storage !== $this->storage->getStorageType()) {
            $song->update([
                'path' => $uploadReference->location,
                'storage' => $this->storage->getStorageType(),
            ]);
        }

        return $song;
    }

    private function handleUploadFailure(UploadReference $reference, Throwable|string $error): never
    {
        $this->storage->undoUpload($reference);

        throw SongUploadFailedException::make($error);
    }
}
