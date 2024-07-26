<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MediaMetadataService
{
    public function __construct(
        private readonly SpotifyService $spotifyService,
        private readonly ImageWriter $imageWriter
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
     * @param string|null $destination The destination path. Automatically generated if empty.
     */
    public function writeAlbumCover(Album $album, string $source, ?string $destination = '', bool $cleanUp = true): void
    {
        attempt(function () use ($album, $source, $destination, $cleanUp): void {
            $destination = $destination ?: $this->generateAlbumCoverPath();
            $this->imageWriter->write($destination, $source);

            if ($cleanUp) {
                $this->deleteAlbumCoverFiles($album);
            }

            $album->update(['cover' => basename($destination)]);
            $this->createThumbnailForAlbum($album);
        });
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
     * @param string|null $destination The destination path. Automatically generated if empty.
     */
    public function writeArtistImage(
        Artist $artist,
        string $source,
        ?string $destination = '',
        bool $cleanUp = true
    ): void {
        attempt(function () use ($artist, $source, $destination, $cleanUp): void {
            $destination = $destination ?: $this->generateArtistImagePath();
            $this->imageWriter->write($destination, $source);

            if ($cleanUp && $artist->has_image) {
                File::delete($artist->image_path);
            }

            $artist->update(['image' => basename($destination)]);
        });
    }

    public function writePlaylistCover(Playlist $playlist, string $source): void
    {
        attempt(function () use ($playlist, $source): void {
            $destination = $this->generatePlaylistCoverPath();
            $this->imageWriter->write($destination, $source);

            if ($playlist->cover_path) {
                File::delete($playlist->cover_path);
            }

            $playlist->update(['cover' => basename($destination)]);
        });
    }

    private function generateAlbumCoverPath(): string
    {
        return album_cover_path(sprintf('%s.webp', sha1(Str::uuid())));
    }

    private function generateArtistImagePath(): string
    {
        return artist_image_path(sprintf('%s.webp', sha1(Str::uuid())));
    }

    private function generatePlaylistCoverPath(): string
    {
        return playlist_cover_path(sprintf('%s.webp', sha1(Str::uuid())));
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

        if (!File::exists($album->thumbnail_path)) {
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

        File::delete($album->cover_path);
        File::delete($album->thumbnail_path);
    }

    public function deletePlaylistCover(Playlist $playlist): void
    {
        if ($playlist->cover_path) {
            File::delete($playlist->cover_path);
            $playlist->update(['cover' => null]);
        }
    }
}
