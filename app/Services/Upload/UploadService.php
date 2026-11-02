<?php

namespace App\Services\Upload;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Repositories\DuplicateUploadRepository;
use App\Repositories\SongRepository;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use App\Services\Concerns\ScansAndStoresSong;
use App\Services\LibrarySummaryService;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Services\Transcoding\TranscodeOnScan;
use App\Values\Song\SongFileInfo;
use App\Values\UploadReference;
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
        private readonly TranscodeOnScan $transcodeOnScan,
        private readonly AnalyzeAudioOnScan $analyzeAudioOnScan,
        private readonly LibrarySummaryService $librarySummaryService,
        private readonly SongRepository $songRepository,
        private readonly DuplicateUploadRepository $duplicateUploadRepository,
    ) {}

    public function handleUpload(string $filePath, User $uploader): Song
    {
        return $this->handleStoredUpload($this->storage->storeUploadedFile($filePath, $uploader), $uploader);
    }

    public function handleStoredUpload(UploadReference $uploadReference, User $uploader): Song
    {
        try {
            $this->duplicateUploadService->detectDuplicate($uploadReference->localPath, $uploadReference, $uploader);

            $song = $this->scanAndStore(
                $uploadReference->localPath,
                $uploadReference->location,
                $uploadReference->originalFileName,
                $uploader,
                $this->scanner,
                $this->songService,
                $this->storage,
                $this->transcodeOnScan,
                $this->analyzeAudioOnScan,
            );

            $this->librarySummaryService->markLibraryChanged();

            return $song;
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

    public function discardFailedStoredUpload(string $location): void
    {
        $isKept =
            $this->songRepository->findOneByPath($location)
            || $this->duplicateUploadRepository->existsAtLocation($location);

        if ($isKept) {
            return;
        }

        $this->storage->delete(SongFileInfo::fromLocation($location, $this->storage->getStorageType())->location);
    }
}
