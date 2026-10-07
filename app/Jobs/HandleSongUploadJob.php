<?php

namespace App\Jobs;

use App\Models\Song;
use App\Models\User;
use App\Services\Upload\UploadService;

class HandleSongUploadJob extends QueuedJob
{
    public function __construct(
        public readonly string $filePath,
        public readonly User $uploader,
    ) {}

    public function handle(UploadService $uploadService): Song
    {
        $song = $uploadService->handleUpload($this->filePath, $this->uploader);

        if ($this->wasQueued()) {
            broadcast($uploadService->makeUploadResponse($song, $this->uploader));
        }

        return $song;
    }
}
