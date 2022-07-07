<?php

namespace App\Listeners;

use App\Events\MediaSyncCompleted;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\Helper;

class DeleteNonExistingRecordsPostSync
{
    public function __construct(private SongRepository $songRepository)
    {
    }

    public function handle(MediaSyncCompleted $event): void
    {
        $hashes = $event->result
            ->validEntries()
            ->map(static fn (string $path): string => Helper::getFileHash($path))
            ->merge($this->songRepository->getAllHostedOnS3()->pluck('id'))
            ->toArray();

        Song::deleteWhereIDsNotIn($hashes);
    }
}
