<?php

namespace App\Services;

use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class ArtworkService
{
    public function __construct(
        private readonly ImageWriter $imageWriter,
        private readonly Finder $finder
    ) {
    }

    /**
     * Write an album cover image file and update the Album with the new cover attribute.
     *
     * @param string $source Path, URL, or even binary data. See https://image.intervention.io/v2/api/make.
     * @param string|null $destination The destination path. Automatically generated if empty.
     */
    public function storeAlbumCover(
        Album $album,
        string $source,
        ?string $destination = '',
        bool $cleanUp = true,
    ): ?string {
        return rescue(function () use ($album, $source, $destination, $cleanUp): string {
            $destination = $destination ?: $this->generateAlbumCoverPath();
            $this->imageWriter->write($destination, $source);

            if ($cleanUp) {
                $this->deleteAlbumCoverFiles($album);
            }

            $album->update(['cover' => basename($destination)]);
            $this->createThumbnailForAlbum($album);

            return $album->cover;
        });
    }

    /**
     * Write an artist image file update the Artist with the new image attribute.
     *
     * @param string $source Path, URL, or even binary data. See https://image.intervention.io/v2/api/make.
     * @param string|null $destination The destination path. Automatically generated if empty.
     */
    public function storeArtistImage(
        Artist $artist,
        string $source,
        ?string $destination = '',
        bool $cleanUp = true
    ): ?string {
        return rescue(function () use ($artist, $source, $destination, $cleanUp): string {
            $destination = $destination ?: $this->generateArtistImagePath();
            $this->imageWriter->write($destination, $source);

            if ($cleanUp && $artist->has_image) {
                File::delete($artist->image_path);
            }

            $artist->update(['image' => basename($destination)]);

            return $artist->image;
        });
    }

    public function storePlaylistCover(Playlist $playlist, string $source): ?string
    {
        return rescue(function () use ($playlist, $source): string {
            $destination = $this->generatePlaylistCoverPath();
            $this->imageWriter->write($destination, $source);

            if ($playlist->cover_path) {
                File::delete($playlist->cover_path);
            }

            $playlist->update(['cover' => basename($destination)]);

            return $playlist->cover;
        });
    }

    private function generateAlbumCoverPath(): string
    {
        return album_cover_path(sprintf('%s.webp', Ulid::generate()));
    }

    private function generateArtistImagePath(): string
    {
        return artist_image_path(sprintf('%s.webp', Ulid::generate()));
    }

    private function generatePlaylistCoverPath(): string
    {
        return playlist_cover_path(sprintf('%s.webp', Ulid::generate()));
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

    public function trySetAlbumCoverFromDirectory(Album $album, string $directory): void
    {
        // As directory scanning can be expensive, we cache and reuse the result.
        Cache::remember(cache_key($directory, 'cover'), now()->addDay(), function () use ($album, $directory): ?string {
            $matches = array_keys(
                iterator_to_array(
                    $this->finder::create()
                        ->depth(0)
                        ->ignoreUnreadableDirs()
                        ->files()
                        ->followLinks()
                        ->name('/(cov|fold)er\.(jpe?g|png)$/i')
                        ->in($directory)
                )
            );

            $cover = $matches[0] ?? null;

            if ($cover && is_image($cover)) {
                $this->storeAlbumCover($album, $cover);
            }

            return $cover;
        });
    }
}
