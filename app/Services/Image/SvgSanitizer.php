<?php

namespace App\Services\Image;

use enshrined\svgSanitize\Sanitizer;

class SvgSanitizer
{
    public function __construct(
        private readonly Sanitizer $sanitizer,
    ) {}

    public function sanitize(string $svg): false|string
    {
        return $this->sanitizer->sanitize($svg);
    }
}
