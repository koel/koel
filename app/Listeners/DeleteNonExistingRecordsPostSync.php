<?php

namespace App\Listeners;

use App\Events\MediaSyncCompleted;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\Helper;
use App\Values\SyncResult;

class DeleteNonExistingRecordsPostSync
{
    public function __construct(private SongRepository $songRepository)
    {
    }

    public function handle(MediaSyncCompleted $event): void
    {
        $hashes = $event->results
            ->valid()
            ->map(static fn (SyncResult $result) => Helper::getFileHash($result->path))
            ->merge($this->songRepository->getAllHostedOnS3()->pluck('id'))
            ->toArray();

        Song::deleteWhereIDsNotIn($hashes);
    }
}
