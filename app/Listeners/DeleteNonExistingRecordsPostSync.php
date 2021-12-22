<?php

namespace App\Listeners;

use App\Events\MediaSyncCompleted;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\Helper;

class DeleteNonExistingRecordsPostSync
{
    private SongRepository $songRepository;
    private Helper $helper;

    public function __construct(SongRepository $songRepository, Helper $helper)
    {
        $this->songRepository = $songRepository;
        $this->helper = $helper;
    }

    public function handle(MediaSyncCompleted $event): void
    {
        $hashes = $event->result
            ->validEntries()
            ->map(fn (string $path): string => $this->helper->getFileHash($path))
            ->merge($this->songRepository->getAllHostedOnS3()->pluck('id'))
            ->toArray();

        Song::deleteWhereIDsNotIn($hashes);
    }
}
