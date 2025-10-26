<?php

namespace App\Services;

use App\Helpers\Ulid;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImageStorage
{
    public function __construct(
        private readonly ImageWriter $imageWriter,
        private readonly SvgSanitizer $svgSanitizer,
    ) {
    }

    /**
     * Store an image file and return the (randomly generated) file name.
     *
     * @param mixed $source Any kind of image data that Intervention can read.
     * @param ?string $path The path to store the image file. Randomly generated if not provided.
     *
     * @return string The file name.
     */
    public function storeImage(mixed $source, ?ImageWritingConfig $config = null, ?string $path = null): string
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

            $path ??= self::generateRandomStoragePath('svg');

            File::put($path, $sanitized);

            return basename($path);
        }

        $path ??= self::generateRandomStoragePath();
        $this->imageWriter->write($path, $source, $config);

        return basename($path);
    }

    private static function generateRandomStoragePath(string $extension = 'webp'): string
    {
        return image_storage_path(sprintf("%s.%s", Ulid::generate(), $extension));
    }
}
