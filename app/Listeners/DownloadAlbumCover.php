<?php

namespace App\Listeners;

use App\Events\AlbumInformationFetched;
use App\Services\MediaMetadataService;

class DownloadAlbumCover
{
    private $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    public function handle(AlbumInformationFetched $event)
    {
        $info = $event->getInformation();
        $album = $event->getAlbum();

        $image = array_get($info, 'image');

        // If our current album has no cover, and Last.fm has one, why don't we steal it?
        if (!$album->has_cover && is_string($image) && ini_get('allow_url_fopen')) {
            $this->mediaMetadataService->downloadAlbumCover($album, $image);
        }
    }
}
