<?php

namespace App\Services\Image;

use App\Values\ImageWritingConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\FileExtension;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;
use Throwable;

class ImageWriter
{
    private FileExtension $extension;

    public function __construct()
    {
        $this->extension = self::getExtension();
    }

    private static function getExtension(): FileExtension
    {
        /** @var ImageManagerInterface $manager */
        $manager = Image::getFacadeRoot();

        // Prioritize AVIF over WEBP over JPEG.
        foreach ([FileExtension::AVIF, FileExtension::WEBP, FileExtension::JPEG] as $extension) {
            if ($manager->driver()->supports($extension)) {
                return $extension;
            }
        }

        throw new RuntimeException('No supported image extension found.');
    }

    public function write(string $destination, mixed $source, ?ImageWritingConfig $config = null): void
    {
        $config ??= ImageWritingConfig::default();

        if (Str::isUrl($source)) {
            try {
                $source = Http::withUserAgent(http_user_agent())
                    ->get($source)
                    ->throwIfClientError()
                    ->throwIfServerError()
                    ->body();
            } catch (Throwable $e) {
                throw new RuntimeException('Failed to fetch image from URL: ' . $source, previous: $e);
            }
        }

        $img = Image::read($source)->scale(width: $config->maxWidth);

        if ($config->blur) {
            $img->blur($config->blur);
        }

        $img->save($destination, $config->quality, $this->extension);
    }
}
