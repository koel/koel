<?php

namespace App\Services;

use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Services\Scanner\FileScanner;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Values\Scanning\ScanConfiguration;
use App\Values\UploadReference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Throwable;

readonly class UploadService
{
    public function __construct(private SongStorage $storage, private FileScanner $scanner)
    {
    }

    public function handleUpload(UploadedFile $file, User $uploader): Song
    {
        $uploadReference = $this->storage->storeUploadedFile($file, $uploader);

        try {
            $result = $this->scanner->setFile($uploadReference->localPath)
                ->scan(ScanConfiguration::make(
                    owner: $uploader,
                    makePublic: $uploader->preferences->makeUploadsPublic,
                    extractFolderStructure: $this->storage->getStorageType()->supportsFolderStructureExtraction(),
                ));
        } catch (Throwable $error) {
            $this->handleUploadFailure($uploadReference, $error);
        }

        if ($result->isError()) {
            $this->handleUploadFailure($uploadReference, $result->error);
        }

        if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
            File::delete($uploadReference->localPath);
        }

        $song = $this->scanner->getSong();

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
