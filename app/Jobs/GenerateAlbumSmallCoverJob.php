<?php

namespace App\Jobs;

use App\Models\Album;
use App\Services\AlbumService;

class GenerateAlbumSmallCoverJob extends QueuedJob
{
    public function __construct(
        private readonly Album $album,
    ) {}

    public function handle(AlbumService $albumService): void
    {
        rescue(fn () => $albumService->generateAlbumSmallCover($this->album));
    }
}
