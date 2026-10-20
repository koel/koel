<?php

namespace App\Services\Upload;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Facades\Dispatcher;
use App\Jobs\DeleteSongFilesJob;
use App\Models\DuplicateUpload;
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
use App\Values\Scanning\ScanInformation;
use App\Values\Song\SongFileInfo;
use App\Values\UploadReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

class DuplicateUploadService
{
    private const string LEGACY_FILE_HASH_ALGORITHM = 'md5';
    private const int KEEP_LOCK_SECONDS = 600;

    use ScansAndStoresSong;

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly DuplicateUploadRepository $duplicateUploadRepository,
        private readonly SongService $songService,
        private readonly FileScanner $scanner,
        private readonly SongStorage $storage,
        private readonly TranscodeOnScan $transcodeOnScan,
        private readonly AnalyzeAudioOnScan $analyzeAudioOnScan,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

    /**
     * @throws DuplicateSongUploadException
     */
    public function detectDuplicate(string $filePath, UploadReference $uploadReference, User $uploader): void
    {
        if (!$uploader->preferences->detectDuplicateUploads) {
            return;
        }

        $existingSong = $this->songRepository->findByAnyHash([
            File::hash($filePath, ScanInformation::FILE_HASH_ALGORITHM),
            File::hash($filePath, self::LEGACY_FILE_HASH_ALGORITHM),
        ], $uploader);

        if (!$existingSong) {
            return;
        }

        /** @var DuplicateUpload $duplicate */
        $duplicate = DuplicateUpload::query()->create([
            'user_id' => $uploader->id,
            'existing_song_id' => $existingSong->id,
            'location' => $uploadReference->location,
            'file_name' => $uploadReference->originalFileName,
            'storage' => $this->storage->getStorageType(),
        ]);

        throw DuplicateSongUploadException::create($filePath, $duplicate);
    }

    /** @return Collection<int, Song> */
    public function keep(Collection $uploads): Collection
    {
        $songs = collect();

        foreach ($uploads as $upload) {
            $song = Cache::lock("keep-duplicate-upload.$upload->id", self::KEEP_LOCK_SECONDS)->get(
                fn (): ?Song => $this->duplicateUploadRepository->findOne($upload->id) ? $this->keepOne($upload) : null,
            );

            if ($song instanceof Song) {
                $songs->add($song);
            }
        }

        return $songs;
    }

    private function keepOne(DuplicateUpload $upload): Song
    {
        $localFilePath = $this->storage->getLocalPath($upload->location);

        try {
            $song = $this->scanAndStore(
                $localFilePath,
                $upload->location,
                $upload->file_name ?? $this->storage->getOriginalFileName($upload->location),
                $upload->user,
                $this->scanner,
                $this->songService,
                $this->storage,
                $this->transcodeOnScan,
                $this->analyzeAudioOnScan,
            );
        } catch (Throwable $error) {
            throw SongUploadFailedException::make($error);
        } finally {
            if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
                File::delete($localFilePath);
            }
        }

        $this->librarySummaryService->markLibraryChanged();
        $upload->delete();

        return $song;
    }

    public function discard(Collection $uploads): void
    {
        $songFiles = $uploads->map(static fn (DuplicateUpload $upload) => SongFileInfo::fromLocation(
            $upload->location,
            $upload->storage,
        ));

        Dispatcher::dispatch(new DeleteSongFilesJob($songFiles));

        foreach ($uploads as $upload) {
            $upload->delete();
        }
    }
}
