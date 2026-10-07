<?php

namespace App\Values;

use App\Enums\ImageChangeType;

final readonly class ImageChange
{
    private function __construct(
        public ImageChangeType $type,
        public ?string $fileName,
    ) {}

    public static function make(ImageChangeType $type, ?string $fileName = null): self
    {
        return new self(type: $type, fileName: $fileName);
    }
}
