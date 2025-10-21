<?php

namespace App\Services;

use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class ImageStorage
{
    public function __construct(
        private readonly ImageWriter $imageWriter,
        private readonly SvgSanitizer $svgSanitizer,
        private readonly Finder $finder,
    ) {
    }

    /**
     * Write an album cover image file and update the Album with the new cover attribute.
     *
     * @param mixed $source Any kind of image data that Intervention can read.
     */
    public function storeAlbumCover(Album $album, mixed $source): ?string
    {
        return rescue(function () use ($album, $source): string {
            $fileName = $this->storeImage($source);
            $album->cover = $fileName;
            $album->save();

            $this->createAlbumThumbnail($album);

            return $fileName;
        });
    }

    /**
     * Write an artist image file update the Artist with the new image attribute.
     *
     * @param mixed $source Any kind of image data that Intervention can read.
     */
    public function storeArtistImage(Artist $artist, mixed $source): ?string
    {
        return rescue(function () use ($artist, $source): string {
            $fileName = $this->storeImage($source);
            $artist->image = $fileName;
            $artist->save();

            return $fileName;
        });
    }

    /**
     * Get an album's thumbnail file name.
     * Auto-generate the thumbnail when possible if one doesn't exist.
     */
    public function getOrCreateAlbumThumbnail(Album $album): ?string
    {
        $thumbnailPath = image_storage_path($album->thumbnail);

        if ($thumbnailPath && !File::exists($thumbnailPath)) {
            $this->createAlbumThumbnail($album);
        }

        return $album->thumbnail;
    }

    private function createAlbumThumbnail(Album $album): void
    {
        rescue_if($album->cover, function () use ($album): void {
            $this->imageWriter->write(
                destination: image_storage_path($album->thumbnail),
                source: image_storage_path($album->cover),
                config: ImageWritingConfig::make(
                    maxWidth: 48,
                    blur: 10,
                ),
            );
        });
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

    /**
     * Store an image file and return the (randomly generated) file name.
     *
     * @param mixed $source Any kind of image data that Intervention can read.
     *
     * @return string The file name.
     */
    public function storeImage(mixed $source, ?ImageWritingConfig $config = null): string
    {
        preg_match('/^data:(image\/[A-Za-z0-9+\-.]+);base64,/', $source, $matches);
        $mime = $matches[1] ?? null;

        if ($mime === 'image/svg+xml') {
            $svgData = preg_replace('/^data:image\/svg\+xml;base64,/', '', $source);
            $raw = base64_decode($svgData, true);

            if ($raw === false) {
                throw new RuntimeException('Failed to decode base64 SVG data.');
            }

            $sanitized = $this->svgSanitizer->sanitize($raw);

            if (!$sanitized) {
                throw new RuntimeException('Invalid SVG file.');
            }

            $path = self::generateRandomStoragePath('svg');

            File::put($path, $sanitized);

            return basename($path);
        }

        $destination = self::generateRandomStoragePath();
        $this->imageWriter->write($destination, $source, $config);

        return basename($destination);
    }

    private static function generateRandomStoragePath(string $extension = 'webp'): string
    {
        return image_storage_path(sprintf("%s.%s", Ulid::generate(), $extension));
    }
}
