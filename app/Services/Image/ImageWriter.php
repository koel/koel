<?php

namespace App\Services\Image;

use App\Values\ImageWritingConfig;
use Illuminate\Container\Attributes\Config;
use Illuminate\Image\Image as ProcessableImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use RuntimeException;
use Throwable;

class ImageWriter
{
    /** In descending order of preference: the first format the driver supports wins. */
    private const array FORMATS = ['avif', 'webp', 'jpg'];

    private const string FALLBACK_FORMAT = 'webp';

    private readonly string $format;

    public function __construct(#[Config('images.default')] string $driver = 'gd')
    {
        $this->format = self::findSupportedFormat($driver);
    }

    public function format(): string
    {
        return $this->format;
    }

    private static function findSupportedFormat(string $driver): string
    {
        $imageDriver = match ($driver) {
            'gd' => new GdDriver(),
            'imagick' => new ImagickDriver(),
            // A custom driver can't be introspected, so assume only the format every driver encodes.
            default => null,
        };

        if (!$imageDriver) {
            return self::FALLBACK_FORMAT;
        }

        foreach (self::FORMATS as $format) {
            if ($imageDriver->supports($format)) {
                return $format;
            }
        }

        throw new RuntimeException('No supported image format found.');
    }

    public function write(string $destination, mixed $source, ?ImageWritingConfig $config = null): void
    {
        $config ??= ImageWritingConfig::default();

        $image = self::read($source)
            ->scale(width: $config->maxWidth)
            ->when($config->blur, static fn (ProcessableImage $image) => $image->blur($config->blur))
            ->optimize($this->format, $config->quality);

        throw_if(
            File::put($destination, $image->toBytes()) === false,
            RuntimeException::class,
            "Failed to write image to $destination",
        );
    }

    /**
     * @param mixed $source A data URI, a base64-encoded image, a URL, a local file path, or raw image bytes.
     */
    private static function read(mixed $source): ProcessableImage
    {
        if (Str::startsWith($source, 'data:')) {
            return Image::fromBase64(Str::after($source, 'base64,'));
        }

        if (Str::isUrl($source)) {
            return Image::fromBytes(self::fetch($source));
        }

        return self::isLocalFile($source) ? Image::fromPath($source) : Image::fromBytes($source);
    }

    private static function isLocalFile(string $source): bool
    {
        return strlen($source) < PHP_MAXPATHLEN && is_file($source);
    }

    /**
     * Koel poses as a browser here, as some hosts serve images only to recognized user agents.
     */
    private static function fetch(string $url): string
    {
        try {
            return Http::withUserAgent(http_user_agent())
                ->get($url)
                ->throwIfClientError()
                ->throwIfServerError()
                ->body();
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to fetch image from URL: ' . $url, previous: $e);
        }
    }
}
