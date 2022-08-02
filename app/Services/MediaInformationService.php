<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Throwable;

class MediaInformationService
{
    public function __construct(
        private LastfmService $lastfmService,
        private MediaMetadataService $mediaMetadataService
    ) {
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        $info = $this->lastfmService->getAlbumInformation($album) ?: AlbumInformation::make();

        if (!$album->has_cover) {
            try {
                $this->mediaMetadataService->tryDownloadAlbumCover($album);
                $info->cover = $album->cover;
            } catch (Throwable) {
            }
        }

        return $info;
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown) {
            return null;
        }

        $info = $this->lastfmService->getArtistInformation($artist) ?: ArtistInformation::make();

        if (!$artist->has_image) {
            try {
                $this->mediaMetadataService->tryDownloadArtistImage($artist);
                $info->image = $artist->image;
            } catch (Throwable) {
            }
        }

        return $info;
    }
}
