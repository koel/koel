<?php

namespace App\Listeners;

use App\Events\AlbumInformationFetched;
use App\Services\MediaMetadataService;
use Throwable;

class DownloadAlbumCover
{
    private $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    public function handle(AlbumInformationFetched $event): void
    {
        $info = $event->getInformation();
        $album = $event->getAlbum();

        $image = array_get($info, 'image');

        // If our current album has no cover, and Last.fm has one, steal it?
        if (!$album->has_cover && $image && ini_get('allow_url_fopen')) {
            try {
                $this->mediaMetadataService->downloadAlbumCover($album, $image);
            } catch (Throwable $e) {
            }
        }
    }
}
