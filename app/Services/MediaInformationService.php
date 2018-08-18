<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Exception;
use Log;

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
        $image = array_get($info, 'image');

        // If our current album has no cover, and Last.fm has one, why don't we steal it?
        if (!$album->has_cover && is_string($image) && ini_get('allow_url_fopen')) {
            try {
                $extension = explode('.', $image);
                $album->writeCoverFile(file_get_contents($image), last($extension));
                $info['cover'] = $album->cover;
            } catch (Exception $e) {
                Log::error($e);
            }
        }

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
        $image = array_get($info, 'image');

        // If our current artist has no image, and Last.fm has one, copy the image for our local use.
        if (!$artist->has_image && is_string($image) && ini_get('allow_url_fopen')) {
            try {
                $extension = explode('.', $image);
                $artist->writeImageFile(file_get_contents($image), last($extension));
                $info['image'] = $artist->image;
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        return $info;
    }
}
