<?php

namespace App\Services;

use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;

class ImageWriter
{
    private const DEFAULT_MAX_WIDTH = 500;
    private const DEFAULT_QUALITY = 80;

    private ImageManager $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function writeFromBinaryData(string $destination, string $data, array $config = []): void
    {
        $img = $this->imageManager
            ->make($data)
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

        $img->save($destination, $config['quality'] ?? self::DEFAULT_QUALITY);
    }
}
