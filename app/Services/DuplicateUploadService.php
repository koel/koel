<?php

namespace App\Services;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Facades\Dispatcher;
use App\Jobs\DeleteSongFilesJob;
use App\Models\DuplicateUpload;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\Concerns\ScansAndStoresSong;
use App\Services\Scanners\FileScanner;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Values\Song\SongFileInfo;
use App\Values\UploadReference;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Throwable;

class DuplicateUploadService
{
    use ScansAndStoresSong;

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly SongService $songService,
        private readonly FileScanner $scanner,
        private readonly SongStorage $storage,
        #[Config('koel.detect_duplicate_uploads')]
        private readonly bool $detectionEnabled = true,
    ) {}

    /**
     * @throws DuplicateSongUploadException
     */
    public function detectAndHandle(string $filePath, UploadReference $uploadReference, User $uploader): void
    {
        if (!$this->detectionEnabled) {
            return;
        }

        $existingSong = $this->songRepository->findByHash(File::hash($filePath), $uploader);

        if (!$existingSong) {
            return;
        }

        /** @var DuplicateUpload $duplicate */
        $duplicate = DuplicateUpload::query()->create([
            'user_id' => $uploader->id,
            'existing_song_id' => $existingSong->id,
            'location' => $uploadReference->location,
            'storage' => $this->storage->getStorageType(),
        ]);

        throw DuplicateSongUploadException::create($filePath, $duplicate);
    }

    /** @param Collection<DuplicateUpload> $uploads */
    public function keep(Collection $uploads): void
    {
        foreach ($uploads as $upload) {
            $localFilePath = $this->storage->getLocalPath($upload->location);

            try {
                $this->scanAndStore(
                    $localFilePath,
                    $upload->location,
                    $upload->user,
                    $this->scanner,
                    $this->songService,
                    $this->storage,
                );
            } catch (Throwable $error) {
                throw SongUploadFailedException::make($error);
            } finally {
                if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
                    File::delete($localFilePath);
                }
            }

            $upload->delete();
        }
    }

    /** @param Collection<DuplicateUpload> $uploads */
    public function discard(Collection $uploads): void
    {
        $songFiles = $uploads->map(static fn (DuplicateUpload $upload) => SongFileInfo::make(
            $upload->location,
            $upload->storage,
        ));

        Dispatcher::dispatch(new DeleteSongFilesJob($songFiles));

        foreach ($uploads as $upload) {
            $upload->delete();
        }
    }
}
