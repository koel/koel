<?php

namespace App\Listeners;

use App\Events\SongFolderStructureExtractionRequested;
use App\Services\MediaBrowser;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class ExtractSongFolderStructure implements ShouldQueue
{
    public function __construct(private MediaBrowser $browser)
    {
    }

    public function handle(SongFolderStructureExtractionRequested $event): void
    {
        $this->browser->maybeCreateFolderStructureForSong($event->song);
    }
}
