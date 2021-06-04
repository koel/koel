<?php

namespace App\Services;

use App\Events\AlbumInformationFetched;
use App\Events\ArtistInformationFetched;
use App\Models\Album;
use App\Models\Artist;

class MediaInformationService
{
    private LastfmService $lastfmService;

    public function __construct(LastfmService $lastfmService)
    {
        $this->lastfmService = $lastfmService;
    }

    /**
     * Get extra information about an album from Last.fm.
     *
     * @return array<mixed>|null the album info in an array format, or null on failure
     */
    public function getAlbumInformation(Album $album): ?array
    {
        if ($album->is_unknown) {
            return null;
        }

        $info = $this->lastfmService->getAlbumInformation($album->name, $album->artist->name);

        if ($info) {
            event(new AlbumInformationFetched($album, $info));

            // The album may have been updated.
            $album->refresh();
            $info['cover'] = $album->cover;
        }

        return $info;
    }

    /**
     * Get extra information about an artist from Last.fm.
     *
     * @return array<mixed>|null the artist info in an array format, or null on failure
     */
    public function getArtistInformation(Artist $artist): ?array
    {
        if ($artist->is_unknown) {
            return null;
        }

        $info = $this->lastfmService->getArtistInformation($artist->name);

        if ($info) {
            event(new ArtistInformationFetched($artist, $info));

            // The artist may have been updated.
            $artist->refresh();
            $info['image'] = $artist->image;
        }

        return $info;
    }
}
