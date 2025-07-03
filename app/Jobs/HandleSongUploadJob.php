<?php

namespace App\Jobs;

use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadResponse;
use App\Services\UploadService;

class HandleSongUploadJob extends QueuedJob
{
    public function __construct(public readonly string $filePath, public readonly User $uploader)
    {
    }

    public function handle(
        UploadService $uploadService,
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
    ): Song {
        $song = $uploadService->handleUpload($this->filePath, $this->uploader);

        $populatedSong = $songRepository->getOne($song->id, $this->uploader);
        $album = $albumRepository->getOne($populatedSong->album_id, $this->uploader);

        broadcast(SongUploadResponse::make(song: $populatedSong, album: $album));

        return $song;
    }
}
