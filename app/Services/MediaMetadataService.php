<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Exception;
use Psr\Log\LoggerInterface;

class MediaMetadataService
{
    private $imageWriter;
    private $logger;

    public function __construct(ImageWriter $imageWriter, LoggerInterface $logger)
    {
        $this->imageWriter = $imageWriter;
        $this->logger = $logger;
    }

    /**
     * Download a copy of the album cover.
     */
    public function downloadAlbumCover(Album $album, string $imageUrl): void
    {
        $extension = explode('.', $imageUrl);
        $this->writeAlbumCover($album, file_get_contents($imageUrl), last($extension));
    }

    /**
     * Write an album cover image file with binary data and update the Album with the new cover attribute.
     *
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeAlbumCover(Album $album, string $binaryData, string $extension, string $destination = ''): void
    {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateAlbumCoverPath($extension);
            $this->imageWriter->writeFromBinaryData($destination, $binaryData);

            $album->update(['cover' => basename($destination)]);
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Download a copy of the artist image.
     */
    public function downloadArtistImage(Artist $artist, string $imageUrl): void
    {
        $extension = explode('.', $imageUrl);
        $this->writeArtistImage($artist, file_get_contents($imageUrl), last($extension));
    }

    /**
     * Write an artist image file with binary data and update the Artist with the new image attribute.
     *
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeArtistImage(
        Artist $artist,
        string $binaryData,
        string $extension,
        string $destination = ''
    ): void {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateArtistImagePath($extension);
            $this->imageWriter->writeFromBinaryData($destination, $binaryData);

            $artist->update(['image' => basename($destination)]);
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Generate the absolute path for an album cover image.
     *
     * @param string $extension The extension of the cover (without dot)
     */
    private function generateAlbumCoverPath(string $extension): string
    {
        return sprintf('%s/public/img/covers/%s.%s', app()->publicPath(), sha1(uniqid()), $extension);
    }

    /**
     * Generate the absolute path for an artist image.
     *
     * @param string $extension The extension of the cover (without dot)
     */
    private function generateArtistImagePath($extension): string
    {
        return sprintf('%s/public/img/artists/%s.%s', app()->publicPath(), sha1(uniqid()), $extension);
    }
}
