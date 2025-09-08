<?php

namespace App\Services;

use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class ImageStorage
{
    public function __construct(
        private readonly ImageWriter $imageWriter,
        private readonly Finder $finder,
    ) {
    }

    /**
     * Write an album cover image file and update the Album with the new cover attribute.
     *
     * @param string $source Path, URL, or even binary data.
     * See https://image.intervention.io/v3/basics/instantiation#read-image-sources.
     * @param string|null $destination The destination path. Automatically generated if empty.
     */
    public function storeAlbumCover(Album $album, string $source, ?string $destination = ''): ?string
    {
        $destination = $destination ?: self::generateRandomStoragePath();

        return rescue(function () use ($album, $source, $destination): string {
            $this->imageWriter->write($destination, $source);
            $album->cover = basename($destination);
            $album->save();

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
        $destination = $destination ?: self::generateRandomStoragePath();

        return rescue(function () use ($artist, $source, $destination): string {
            $this->imageWriter->write($destination, $source);
            $artist->image = basename($destination);
            $artist->save();

            return $artist->image;
        });
    }

    /**
     * Get the URL of an album's thumbnail.
     * Auto-generate the thumbnail when possible if one doesn't exist.
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
        $this->imageWriter->write($album->thumbnail_path, $album->cover_path, ImageWritingConfig::make(
            maxWidth: 48,
            blur: 10,
        ));
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
                        ->name('/(cov|fold)er\.(jpe?g|gif|png|webp|avif)$/i')
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

    public function storeImage(mixed $source, ?ImageWritingConfig $config = null): string
    {
        $destination = self::generateRandomStoragePath();
        $this->imageWriter->write($destination, $source, $config);

        return basename($destination);
    }

    private static function generateRandomStoragePath(): string
    {
        return image_storage_path(sprintf('%s.webp', Ulid::generate()));
    }
}
