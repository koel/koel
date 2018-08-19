<?php

namespace App\Services;

use App\Events\AlbumInformationFetched;
use App\Events\ArtistInformationFetched;
use App\Models\Album;
use App\Models\Artist;

class MediaInformationService
{
    private $lastfmService;

    public function __construct(LastfmService $lastfmService)
    {
        $this->lastfmService = $lastfmService;
    }

    /**
     * Get extra information about an album from Last.fm.
     *
     * @param Album $album
     *
     * @return array|false The album info in an array format, or false on failure.
     */
    public function getAlbumInformation(Album $album)
    {
        if ($album->is_unknown) {
            return false;
        }

        $info = $this->lastfmService->getAlbumInfo($album->name, $album->artist->name);
        event(new AlbumInformationFetched($album, $info));

        // The album may have been updated.
        $album->refresh();
        $info['cover'] = $album->cover;

        return $info;
    }

    /**
     * Get extra information about an artist from Last.fm.
     *
     * @param Artist $artist
     *
     * @return array|false The artist info in an array format, or false on failure.
     */
    public function getArtistInformation(Artist $artist)
    {
        if ($artist->is_unknown) {
            return false;
        }

        $info = $this->lastfmService->getArtistInfo($artist->name);
        event(new ArtistInformationFetched($artist, $info));

        // The artist may have been updated.
        $artist->refresh();
        $info['image'] = $artist->image;

        return $info;
    }
}
