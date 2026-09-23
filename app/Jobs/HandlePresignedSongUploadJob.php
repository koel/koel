<?php

namespace App\Jobs;

use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
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
    ): Song {
        $reference = UploadReference::make(
            location: $this->location,
            localPath: $storage->getLocalPath($this->location),
        );

        $song = $uploadService->handleStoredUpload($reference, $this->uploader);

        $populatedSong = $songRepository->getOne($song->id, $this->uploader);
        $album = $albumRepository->getOne($populatedSong->album_id, $this->uploader);

        broadcast(SongUploadResponse::make(song: $populatedSong, album: $album, uploadKey: $this->uploadKey));

        return $song;
    }

    public function failed(Throwable $exception): void
    {
        broadcast(SongUploadFailedResponse::make(
            uploader: $this->uploader,
            uploadKey: $this->uploadKey,
            message: $exception->getMessage(),
        ));
    }
}
