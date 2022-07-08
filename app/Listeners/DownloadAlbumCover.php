<?php

namespace App\Listeners;

use App\Events\AlbumInformationFetched;
use App\Services\MediaMetadataService;
use Throwable;

class DownloadAlbumCover
{
    public function __construct(private MediaMetadataService $mediaMetadataService)
    {
    }

    public function handle(AlbumInformationFetched $event): void
    {
        // If our current album has no cover, and Last.fm has one, steal it?
        if (!$event->album->has_cover && $event->information->cover && ini_get('allow_url_fopen')) {
            try {
                $this->mediaMetadataService->downloadAlbumCover($event->album, $event->information->cover);
            } catch (Throwable) {
            }
        }
    }
}
