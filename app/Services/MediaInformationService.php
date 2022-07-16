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
        private SpotifyService $spotifyService,
        private MediaMetadataService $mediaMetadataService
    ) {
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        $info = $this->lastfmService->getAlbumInformation($album) ?: new AlbumInformation();

        if (!$album->has_cover) {
            try {
                $cover = $this->spotifyService->tryGetAlbumCover($album);

                if ($cover) {
                    $this->mediaMetadataService->downloadAlbumCover($album, $cover);
                    $info->cover = $album->refresh()->cover;
                }
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

        $info = $this->lastfmService->getArtistInformation($artist) ?: new ArtistInformation();

        if (!$artist->has_image) {
            try {
                $image = $this->spotifyService->tryGetArtistImage($artist);

                if ($image) {
                    $this->mediaMetadataService->downloadArtistImage($artist, $image);
                    $info->image = $artist->refresh()->image;
                }
            } catch (Throwable) {
            }
        }

        return $info;
    }
}
