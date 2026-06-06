<?php

namespace App\Services\Upload;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Facades\Dispatcher;
use App\Jobs\DeleteSongFilesJob;
use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\Concerns\ScansAndStoresSong;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Values\Song\SongFileInfo;
use App\Values\UploadReference;
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
    ) {}

    /**
     * @throws DuplicateSongUploadException
     */
    public function detectDuplicate(string $filePath, UploadReference $uploadReference, User $uploader): void
    {
        if (!$uploader->preferences->detectDuplicateUploads) {
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

    /** @return Collection<int, Song> */
    public function keep(Collection $uploads): Collection
    {
        $songs = collect();

        foreach ($uploads as $upload) {
            $localFilePath = $this->storage->getLocalPath($upload->location);

            try {
                $songs->add($this->scanAndStore(
                    $localFilePath,
                    $upload->location,
                    $upload->user,
                    $this->scanner,
                    $this->songService,
                    $this->storage,
                ));
            } catch (Throwable $error) {
                throw SongUploadFailedException::make($error);
            } finally {
                if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
                    File::delete($localFilePath);
                }
            }

            $upload->delete();
        }

        return $songs;
    }

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
