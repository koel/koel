<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Exception;
use Psr\Log\LoggerInterface;
use function App\Helpers\album_cover_path;
use function App\Helpers\artist_image_path;

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
    public function writeAlbumCover(
        Album $album,
        string $binaryData,
        string $extension,
        string $destination = '',
        bool $cleanUp = true
    ): void
    {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateAlbumCoverPath($extension);
            $this->imageWriter->writeFromBinaryData($destination, $binaryData);

            if ($cleanUp) {
                $this->deleteAlbumCoverFiles($album);
            }

            $album->update(['cover' => basename($destination)]);
            $this->createThumbnailForAlbum($album);
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
        string $destination = '',
        bool $cleanUp = true
    ): void {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateArtistImagePath($extension);
            $this->imageWriter->writeFromBinaryData($destination, $binaryData);

            if ($cleanUp && $artist->has_image) {
                @unlink($artist->image_path);
            }

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
        return album_cover_path(sprintf('%s.%s', sha1(uniqid()), $extension));
    }

    /**
     * Generate the absolute path for an artist image.
     *
     * @param string $extension The extension of the cover (without dot)
     */
    private function generateArtistImagePath($extension): string
    {
        return artist_image_path(sprintf('%s.%s', sha1(uniqid()), $extension));
    }

    /**
     * Get the URL of an album's thumbnail.
     * Auto-generate the thumbnail when possible, if one doesn't exist yet.
     */
    public function getAlbumThumbnailUrl(Album $album): ?string
    {
        if (!$album->has_cover) {
            return null;
        }

        if (!file_exists($album->thumbnail_path)) {
            $this->createThumbnailForAlbum($album);
        }

        return $album->thumbnail;
    }

    private function createThumbnailForAlbum(Album $album): void
    {
        $this->imageWriter->writeFromBinaryData(
            $album->thumbnail_path,
            file_get_contents($album->cover_path),
            ['max_width' => 48, 'blur' => 10]
        );
    }

    private function deleteAlbumCoverFiles(Album $album): void
    {
        if (!$album->has_cover) {
            return;
        }

        @unlink($album->cover_path);
        @unlink($album->thumbnail_path);
    }
}
