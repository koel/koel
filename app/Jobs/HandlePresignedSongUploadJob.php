<?php

namespace App\Jobs;

use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadDuplicatedResponse;
use App\Responses\SongUploadFailedResponse;
use App\Responses\SongUploadResponse;
use App\Services\SongStorages\SongStorage;
use App\Services\Upload\UploadService;
use App\Values\UploadReference;
use Throwable;

class HandlePresignedSongUploadJob extends QueuedJob
{
    public function __construct(
        public readonly string $location,
        public readonly string $uploadKey,
        public readonly User $uploader,
    ) {}

    public function handle(
        SongStorage $storage,
        UploadService $uploadService,
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
    ): ?Song {
        $reference = UploadReference::make(
            location: $this->location,
            localPath: $storage->getLocalPath($this->location),
            originalFileName: $storage->getOriginalFileName($this->location),
        );

        try {
            $song = $uploadService->handleStoredUpload($reference, $this->uploader);
        } catch (DuplicateSongUploadException $e) {
            throw_unless($this->wasQueued(), $e);

            broadcast(SongUploadDuplicatedResponse::make(
                uploader: $this->uploader,
                uploadKey: $this->uploadKey,
                duplicateUpload: $e->duplicateUpload,
            ));

            return null;
        }

        $populatedSong = $songRepository->getOne($song->id, $this->uploader);
        $album = $albumRepository->getOne($populatedSong->album_id, $this->uploader);

        if ($this->wasQueued()) {
            broadcast(SongUploadResponse::make(song: $populatedSong, album: $album, uploadKey: $this->uploadKey));
        }

        return $song;
    }

    public function failed(Throwable $exception): void
    {
        rescue(function (): void {
            app(UploadService::class)->discardFailedStoredUpload($this->location);
        });

        if (!$this->wasQueued()) {
            return;
        }

        broadcast(SongUploadFailedResponse::make(
            uploader: $this->uploader,
            uploadKey: $this->uploadKey,
            message: self::describeFailureToUploader($exception),
        ));
    }

    private static function describeFailureToUploader(Throwable $exception): string
    {
        return $exception instanceof SongUploadFailedException
            ? $exception->getUserFacingMessage()
            : SongUploadFailedException::GENERIC_USER_FACING_MESSAGE;
    }
}
