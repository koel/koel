<?php

namespace App\Values;

final readonly class ImageWritingConfig
{
    private const int DEFAULT_QUALITY = 80;
    private const int DEFAULT_MAX_WIDTH = 640;

    private function __construct(
        public int $quality,
        public int $maxWidth,
        public ?int $blur,
    ) {}

    public static function make(
        int $quality = self::DEFAULT_QUALITY,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        ?int $blur = null,
    ): self {
        return new self($quality, $maxWidth, $blur);
    }

    public static function default(): self
    {
        return self::make();
    }
}
