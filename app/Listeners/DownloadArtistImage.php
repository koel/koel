<?php

namespace App\Listeners;

use App\Events\ArtistInformationFetched;
use App\Services\MediaMetadataService;

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
        if (!$artist->has_image && is_string($image) && ini_get('allow_url_fopen')) {
            $this->mediaMetadataService->downloadArtistImage($artist, $image);
        }
    }
}
