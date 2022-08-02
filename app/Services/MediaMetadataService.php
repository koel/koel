<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

class MediaMetadataService
{
    public function __construct(
        private SpotifyService $spotifyService,
        private ImageWriter $imageWriter,
        private LoggerInterface $logger
    ) {
    }

    public function tryDownloadAlbumCover(Album $album): void
    {
        optional($this->spotifyService->tryGetAlbumCover($album), function (string $coverUrl) use ($album): void {
            $this->writeAlbumCover($album, $coverUrl);
        });
    }

    /**
     * Write an album cover image file and update the Album with the new cover attribute.
     *
     * @param string $source Path, URL, or even binary data. See https://image.intervention.io/v2/api/make.
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeAlbumCover(
        Album $album,
        string $source,
        string $extension = 'png',
        ?string $destination = '',
        bool $cleanUp = true
    ): void {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateAlbumCoverPath($extension);
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

    public function tryDownloadArtistImage(Artist $artist): void
    {
        optional($this->spotifyService->tryGetArtistImage($artist), function (string $imageUrl) use ($artist): void {
            $this->writeArtistImage($artist, $imageUrl);
        });
    }

    /**
     * Write an artist image file update the Artist with the new image attribute.
     *
     * @param string $source Path, URL, or even binary data. See https://image.intervention.io/v2/api/make.
     * @param string $destination The destination path. Automatically generated if empty.
     */
    public function writeArtistImage(
        Artist $artist,
        string $source,
        string $extension = 'png',
        ?string $destination = '',
        bool $cleanUp = true
    ): void {
        try {
            $extension = trim(strtolower($extension), '. ');
            $destination = $destination ?: $this->generateArtistImagePath($extension);
            $this->imageWriter->write($destination, $source);

            if ($cleanUp && $artist->has_image) {
                @unlink($artist->image_path);
            }

            $artist->update(['image' => basename($destination)]);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    private function generateAlbumCoverPath(string $extension): string
    {
        return album_cover_path(sprintf('%s.%s', sha1(Str::uuid()), trim($extension, '.')));
    }

    private function generateArtistImagePath(string $extension): string
    {
        return artist_image_path(sprintf('%s.%s', sha1(Str::uuid()), trim($extension, '.')));
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
