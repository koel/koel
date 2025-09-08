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
        private readonly Finder $finder,
    ) {
    }

    /**
     * Write an album cover image file and update the Album with the new cover attribute.
     *
     * @param string $source Path, URL, or even binary data. See https://image.intervention.io/v2/api/make.
     * @param string|null $destination The destination path. Automatically generated if empty.
     */
    public function storeAlbumCover(Album $album, string $source, ?string $destination = ''): ?string
    {
        return rescue(function () use ($album, $source, $destination): string {
            $destination = $destination ?: self::generateImageStoragePath();
            $this->imageWriter->write($destination, $source);

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
    public function storeArtistImage(Artist $artist, string $source, ?string $destination = ''): ?string
    {
        return rescue(function () use ($artist, $source, $destination): string {
            $destination = $destination ?: self::generateImageStoragePath();
            $this->imageWriter->write($destination, $source);

            $artist->update(['image' => basename($destination)]);

            return $artist->image;
        });
    }

    public function storePlaylistCover(Playlist $playlist, string $source): ?string
    {
        return rescue(function () use ($playlist, $source): string {
            $destination = self::generateImageStoragePath();
            $this->imageWriter->write($destination, $source);

            if ($playlist->cover_path) {
                File::delete($playlist->cover_path);
            }

            $playlist->update(['cover' => basename($destination)]);

            return $playlist->cover;
        });
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

    public function storeRadioStationLogo(string $logo): ?string
    {
        $destination = self::generateImageStoragePath();
        $this->imageWriter->write($destination, $logo);

        return basename($destination);
    }

    private static function generateImageStoragePath(): string
    {
        return image_storage_path(sprintf('%s.webp', Ulid::generate()));
    }
}
