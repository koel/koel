<?php

namespace App\Listeners;

use App\Events\ArtistInformationFetched;
use App\Services\MediaMetadataService;
use Throwable;

class DownloadArtistImage
{
    private $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    public function handle(ArtistInformationFetched $event): void
    {
        $info = $event->getInformation();
        $artist = $event->getArtist();

        $image = array_get($info, 'image');

        // If our artist has no image, and Last.fm has one, we steal it?
        if (!$artist->has_image && $image && ini_get('allow_url_fopen')) {
            try {
                $this->mediaMetadataService->downloadArtistImage($artist, $image);
            } catch (Throwable $e) {
            }
        }
    }
}
