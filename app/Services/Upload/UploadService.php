<?php

namespace App\Services\Upload;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Services\Concerns\ScansAndStoresSong;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use Illuminate\Support\Facades\File;
use Throwable;

class UploadService
{
    use ScansAndStoresSong;

    public function __construct(
        private readonly SongService $songService,
        private readonly SongStorage $storage,
        private readonly FileScanner $scanner,
        private readonly DuplicateUploadService $duplicateUploadService,
    ) {}

    public function handleUpload(string $filePath, User $uploader): Song
    {
        $uploadReference = $this->storage->storeUploadedFile($filePath, $uploader);

        try {
            $this->duplicateUploadService->detectDuplicate($uploadReference->localPath, $uploadReference, $uploader);

            return $this->scanAndStore(
                $uploadReference->localPath,
                $uploadReference->location,
                $uploader,
                $this->scanner,
                $this->songService,
                $this->storage,
            );
        } catch (DuplicateSongUploadException $e) {
            throw $e;
        } catch (Throwable $error) {
            $this->storage->undoUpload($uploadReference);

            throw SongUploadFailedException::make($error);
        } finally {
            if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
                File::delete($uploadReference->localPath);
            }
        }
    }
}
