<?php

namespace App\Values;

final readonly class ImageWritingConfig
{
    private function __construct(
        public int $quality,
        public int $maxWidth,
        public ?int $blur,
    ) {
    }

    public static function make(int $quality = 80, int $maxWidth = 500, ?int $blur = 0): self
    {
        return new self($quality, $maxWidth, $blur);
    }

    public static function default(): self
    {
        return new self(quality: 80, maxWidth: 500, blur: null);
    }
}
