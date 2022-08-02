<?php

namespace App\Listeners;

use App\Events\MediaSyncCompleted;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\SyncResult;

class DeleteNonExistingRecordsPostSync
{
    public function __construct(private SongRepository $songRepository)
    {
    }

    public function handle(MediaSyncCompleted $event): void
    {
        $paths = $event->results
            ->valid()
            ->map(static fn (SyncResult $result) => $result->path)
            ->merge($this->songRepository->getAllHostedOnS3()->pluck('path'))
            ->toArray();

        Song::deleteWhereValueNotIn($paths, 'path');
    }
}
