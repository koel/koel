<?php

namespace App\Services\Image;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Gd\Encoders\AvifEncoder;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Encodes AVIF at libavif's fastest speed. Intervention leaves the speed at libavif's default (6), which is about
 * seven times slower for album covers while producing files of almost the same size.
 */
class FastGdAvifEncoder extends AvifEncoder
{
    protected function belongsToDriver(object $driver): bool
    {
        return $driver instanceof GdDriver;
    }

    public function encode(ImageInterface $image): EncodedImageInterface
    {
        return $this->createEncodedImage(fn ($stream) => imageavif(
            $image->core()->native(),
            $stream,
            $this->quality,
            speed: 10,
        ), 'image/avif');
    }
}
