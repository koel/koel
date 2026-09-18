<?php

namespace App\Services\Image;

use App\Helpers\Ulid;
use App\Values\ImageWritingConfig;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImageStorage
{
    public const string DISK = 'images';

    public function __construct(
        private readonly ImageWriter $imageWriter,
        private readonly SvgSanitizer $svgSanitizer,
    ) {}

    /**
     * Store an image and return its file name.
     *
     * @param mixed $source Any kind of image data that Intervention can read.
     * @param ?string $fileName The name to store the image under. Randomly generated if not provided.
     */
    public function storeImage(mixed $source, ?ImageWritingConfig $config = null, ?string $fileName = null): string
    {
        preg_match('/^data:(image\/[A-Za-z0-9+\-.]+);base64,/', $source, $matches);

        if (($matches[1] ?? null) === 'image/svg+xml') {
            return $this->storeSvg($source, $fileName);
        }

        $fileName ??= self::generateRandomFileName($this->imageWriter->format());
        $this->put($fileName, $this->imageWriter->encode($source, $config));

        return $fileName;
    }

    public function exists(?string $fileName): bool
    {
        return (bool) $fileName && self::disk()->exists($fileName);
    }

    public function get(?string $fileName): ?string
    {
        return $this->exists($fileName) ? self::disk()->get($fileName) : null;
    }

    /** @param array<string|null>|string|null $fileNames */
    public function delete(array|string|null $fileNames): void
    {
        $existing = collect(Arr::wrap($fileNames))
            ->filter()
            ->filter($this->exists(...))
            ->all();

        if (!$existing) {
            return;
        }

        throw_if(
            self::disk()->delete($existing) === false,
            RuntimeException::class,
            'Failed to delete ' . implode(', ', $existing),
        );
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    private function storeSvg(string $source, ?string $fileName): string
    {
        $raw = base64_decode(preg_replace('/^data:image\/svg\+xml;base64,/', '', $source), true);

        throw_if($raw === false, RuntimeException::class, 'Failed to decode base64 SVG data.');

        $sanitized = $this->svgSanitizer->sanitize($raw);

        throw_if(!$sanitized, RuntimeException::class, 'Invalid SVG file.');

        $fileName ??= self::generateRandomFileName('svg');
        $this->put($fileName, $sanitized);

        return $fileName;
    }

    private function put(string $fileName, string $contents): void
    {
        throw_if(
            self::disk()->put($fileName, $contents) === false,
            RuntimeException::class,
            "Failed to store image $fileName",
        );
    }

    private static function generateRandomFileName(string $extension): string
    {
        return sprintf('%s.%s', Ulid::generate(), $extension);
    }
}
