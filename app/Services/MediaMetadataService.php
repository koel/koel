<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Psr\Log\LoggerInterface;
use Throwable;

class MediaMetadataService
{
    private ImageWriter $imageWriter;
    private LoggerInterface $logger;

    public function __construct(ImageWriter $imageWriter, LoggerInterface $logger)
    {
        $this->imageWriter = $imageWriter;
        $this->logger = $logger;
    }

    public function downloadAlbumCover(Album $album, string $imageUrl): void
    {
        $this->writeAlbumCover($album, $imageUrl, 'png');
    }

    /**
     * Write an album cover image file with binary data and update the Album with the new cover attribute.
     *
     * @param string $source Path, URL, or even binary data. See https://image.intervention.io/v2/api/make.
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeAlbumCover(
        Album $album,
        string $source,
        string $extension,
        string $destination = '',
        bool $cleanUp = true
    ): void {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateAlbumCoverPath($album, $extension);
            $this->imageWriter->write($destination, $source);

            if ($cleanUp) {
                $this->deleteAlbumCoverFiles($album);
            }

            $album->update(['cover' => basename($destination)]);
            $this->createThumbnailForAlbum($album);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    public function downloadArtistImage(Artist $artist, string $imageUrl): void
    {
        $this->writeArtistImage($artist, $imageUrl, '.png');
    }

    /**
     * Write an artist image file with binary data and update the Artist with the new image attribute.
     *
     * @param string $source Path, URL, or even binary data. See https://image.intervention.io/v2/api/make.
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeArtistImage(
        Artist $artist,
        string $source,
        string $extension,
        string $destination = '',
        bool $cleanUp = true
    ): void {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateArtistImagePath($artist, $extension);
            $this->imageWriter->write($destination, $source);

            if ($cleanUp && $artist->has_image) {
                @unlink($artist->image_path);
            }

            $artist->update(['image' => basename($destination)]);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    private function generateAlbumCoverPath(Album $album, string $extension): string
    {
        return album_cover_path(sprintf('%s.%s', sha1((string) $album->id), trim($extension, '.')));
    }

    private function generateArtistImagePath(Artist $artist, string $extension): string
    {
        return artist_image_path(sprintf('%s.%s', sha1((string) $artist->id), trim($extension, '.')));
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
        $this->imageWriter->write($album->thumbnail_path, $album->cover_path, ['max_width' => 48, 'blur' => 10]);
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
