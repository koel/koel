<?php

namespace App\Services\Image;

use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Imagick\Encoders\AvifEncoder;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Encodes AVIF at libaom's fastest speed. Intervention leaves the speed at libaom's default (6), which is about
 * eight times slower for album covers while producing files of almost the same size.
 */
class FastImagickAvifEncoder extends AvifEncoder
{
    protected function belongsToDriver(object $driver): bool
    {
        return $driver instanceof ImagickDriver;
    }

    public function encode(ImageInterface $image): EncodedImageInterface
    {
        $image->core()->native()->setOption('heic:speed', '9');

        return parent::encode($image);
    }
}
