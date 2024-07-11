<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;

class ImageWriter
{
    private const DEFAULT_MAX_WIDTH = 500;
    private const DEFAULT_QUALITY = 80;

    private string $supportedFormat = 'jpg';

    public function __construct(private readonly ImageManager $imageManager)
    {
        $this->supportedFormat = static::getSupportedFormat();
    }

    private static function getSupportedFormat(): string
    {
        return Arr::get(gd_info(), 'WebP Support') ? 'webp' : 'jpg';
    }

    public function write(string $destination, object|string $source, array $config = []): void
    {
        $img = $this->imageManager
            ->make($source)
            ->resize(
                $config['max_width'] ?? self::DEFAULT_MAX_WIDTH,
                null,
                static function (Constraint $constraint): void {
                    $constraint->upsize();
                    $constraint->aspectRatio();
                }
            );

        if (isset($config['blur'])) {
            $img->blur($config['blur']);
        }

        $img->save($destination, $config['quality'] ?? self::DEFAULT_QUALITY, $this->supportedFormat);
    }
}
