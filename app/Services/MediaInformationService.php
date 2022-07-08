<?php

namespace App\Services;

use App\Events\AlbumInformationFetched;
use App\Events\ArtistInformationFetched;
use App\Models\Album;
use App\Models\Artist;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;

class MediaInformationService
{
    public function __construct(
        private LastfmService $lastfmService,
        private AlbumRepository $albumRepository,
        private ArtistRepository $artistRepository
    ) {
    }

    public function getAlbumInformation(Album $album): ?AlbumInformation
    {
        if ($album->is_unknown) {
            return null;
        }

        $info = $this->lastfmService->getAlbumInformation($album->name, $album->artist->name);

        if ($info) {
            event(new AlbumInformationFetched($album, $info));
            // The album cover may have been updated.
            $info->cover = $this->albumRepository->getOneById($album->id)->cover;
        }

        return $info;
    }

    public function getArtistInformation(Artist $artist): ?ArtistInformation
    {
        if ($artist->is_unknown) {
            return null;
        }

        $info = $this->lastfmService->getArtistInformation($artist->name);

        if ($info) {
            event(new ArtistInformationFetched($artist, $info));
            // The artist image may have been updated.
            $info->image = $this->artistRepository->getOneById($artist->id)->image;
        }

        return $info;
    }
}
