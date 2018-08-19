<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Exception;
use Log;

class MediaMetadataService
{
    /**
     * Download a copy of the album cover.
     *
     * @param Album  $album
     * @param string $imageUrl
     */
    public function downloadAlbumCover(Album $album, $imageUrl)
    {
        $extension = explode('.', $imageUrl);
        $this->writeAlbumCover($album, file_get_contents($imageUrl), last($extension));
    }

    /**
     * Copy a cover file from an existing image on the system.
     *
     * @param Album  $album
     * @param string $source      The original image's full path.
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function copyAlbumCover(Album $album, $source, $destination = '')
    {
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $destination = $destination ?: $this->generateAlbumCoverPath($extension);
        copy($source, $destination);

        $album->update(['cover' => basename($destination)]);
    }

    /**
     * Write an album cover image file with binary data and update the Album with the new cover attribute.
     *
     * @param Album  $album
     * @param string $binaryData
     * @param string $extension   The file extension
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeAlbumCover(Album $album, $binaryData, $extension, $destination = '')
    {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateAlbumCoverPath($extension);
            file_put_contents($destination, $binaryData);

            $album->update(['cover' => basename($destination)]);
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    /**
     * Download a copy of the artist image.
     *
     * @param Artist $artist
     * @param string $imageUrl
     */
    public function downloadArtistImage(Artist $artist, $imageUrl)
    {
        $extension = explode('.', $imageUrl);
        $this->writeArtistImage($artist, file_get_contents($imageUrl), last($extension));
    }

    /**
     * Write an artist image file with binary data and update the Artist with the new image attribute.
     *
     * @param Artist $artist
     * @param string $binaryData
     * @param string $extension   The file extension
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeArtistImage(Artist $artist, $binaryData, $extension, $destination = '')
    {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateArtistImagePath($extension);
            file_put_contents($destination, $binaryData);

            $artist->update(['image' => basename($destination)]);
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    /**
     * Generate a random path for an album cover image.
     *
     * @param string $extension The extension of the cover (without dot)
     *
     * @return string
     */
    private function generateAlbumCoverPath($extension)
    {
        return sprintf('%s/public/img/covers/%s.%s', app()->publicPath(), uniqid('', true), $extension);
    }

    /**
     * Generate a random path for an artist image.
     *
     * @param string $extension The extension of the cover (without dot)
     *
     * @return string
     */
    private function generateArtistImagePath($extension)
    {
        return sprintf('%s/public/img/artists/%s.%s', app()->publicPath(), uniqid('', true), $extension);
    }
}
