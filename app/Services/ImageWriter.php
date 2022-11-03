<?php

namespace App\Services;

use Intervention\Image\Constraint;
use Intervention\Image\Exception\NotSupportedException;
use Intervention\Image\ImageManager;

class ImageWriter
{
    private const DEFAULT_MAX_WIDTH = 500;
    private const DEFAULT_QUALITY = 80;

    public function __construct(private ImageManager $imageManager)
    {
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

        try {
            $img->save($destination, $config['quality'] ?? self::DEFAULT_QUALITY, 'webp');
        } catch (NotSupportedException) {
            $img->save($destination, $config['quality'] ?? self::DEFAULT_QUALITY);
        }
    }
}
