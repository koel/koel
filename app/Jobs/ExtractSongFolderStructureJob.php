<?php

namespace App\Jobs;

use App\Models\Song;
use App\Services\MediaBrowser;

class ExtractSongFolderStructureJob extends QueuedJob
{
    public function __construct(private readonly Song $song)
    {
    }

    public function handle(MediaBrowser $browser): void
    {
        $browser->maybeCreateFolderStructureForSong($this->song);
    }
}
