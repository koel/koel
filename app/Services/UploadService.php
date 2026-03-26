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
        $uploadReference = $this->storage->storeUploadedFile($filePath, $uploader);

        $config = ScanConfiguration::make(
            owner: $uploader,
            makePublic: $uploader->preferences->makeUploadsPublic,
            extractFolderStructure: $this->storage->getStorageType()->supportsFolderStructureExtraction(),
        );

        if ($existingSong) {
            // TODO: we should store the config object and use the upload reference object instead of the filepath
            // this will support the feature with agnostic storage methods. As well, now the song will get stored in the
            // correct spot in the database, but the entry will be in a different table
            // this will allow a future crud repository to either cleanup the song and its data or easily move it
            // into the songs table without having to do any data transformation
            $this->duplicateUploadRepository->create($uploader, $filePath, $existingSong);
            if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
                File::delete($uploadReference->localPath);
            }
            throw DuplicateSongUploadException::fromFilePath($filePath);
        }

        try {
            $song = $this->songService->createOrUpdateSongFromScan(
                $this->scanner->scan($uploadReference->localPath),
                $config,
            );
        } catch (Throwable $error) {
            if ($error instanceof DuplicateSongUploadException) throw $error;
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
