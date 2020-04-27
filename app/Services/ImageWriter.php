<?php

namespace App\Services;

use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;

class ImageWriter
{
    private const MAX_WIDTH = 500;
    private const QUALITY = 80;

    private $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function writeFromBinaryData(string $destination, string $data): void
    {
        $this->imageManager
            ->make($data)
            ->resize(self::MAX_WIDTH, null, static function (Constraint $constraint): void {
                $constraint->upsize();
                $constraint->aspectRatio();
            })
            ->save($destination, self::QUALITY);
    }
}
