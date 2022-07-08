<?php

namespace App\Listeners;

use App\Events\ArtistInformationFetched;
use App\Services\MediaMetadataService;
use Throwable;

class DownloadArtistImage
{
    public function __construct(private MediaMetadataService $mediaMetadataService)
    {
    }

    public function handle(ArtistInformationFetched $event): void
    {
        // If our artist has no image, and Last.fm has one, we steal it?
        if (!$event->artist->has_image && $event->information->image && ini_get('allow_url_fopen')) {
            try {
                $this->mediaMetadataService->downloadArtistImage($event->artist, $event->information->image);
            } catch (Throwable) {
            }
        }
    }
}
