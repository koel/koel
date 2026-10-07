<?php

namespace App\Services\Upload;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadResponse;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use App\Services\Concerns\ScansAndStoresSong;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Services\Transcoding\TranscodeOnScan;
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
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
    ) {}

    /**
     * Build the response for an uploaded song, with the song and its album loaded as the uploader sees them.
     */
    public function makeUploadResponse(Song $song, User $uploader, ?string $uploadKey = null): SongUploadResponse
    {
        $populatedSong = $this->songRepository->getOne($song->id, $uploader);
        $album = $this->albumRepository->getOne($populatedSong->album_id, $uploader);

        return SongUploadResponse::make(song: $populatedSong, album: $album, uploadKey: $uploadKey);
    }

    public function handleUpload(string $filePath, User $uploader): Song
    {
        return $this->handleStoredUpload($this->storage->storeUploadedFile($filePath, $uploader), $uploader);
    }

    public function handleStoredUpload(UploadReference $uploadReference, User $uploader): Song
    {
        try {
            $this->duplicateUploadService->detectDuplicate($uploadReference->localPath, $uploadReference, $uploader);

            return $this->scanAndStore(
                $uploadReference->localPath,
                $uploadReference->location,
                $uploader,
                $this->scanner,
                $this->songService,
                $this->storage,
                $this->transcodeOnScan,
                $this->analyzeAudioOnScan,
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
