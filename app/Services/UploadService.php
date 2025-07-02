<?php

namespace App\Services;

use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
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
    ) {
    }

    public function handleUpload(string $filePath, User $uploader): Song
    {
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
