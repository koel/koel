<?php

namespace App\Values\Theme;

final readonly class ThemeCreateData
{
    private function __construct(
        public string $name,
        public string $fgColor,
        public string $bgColor,
        public string $bgImage,
        public string $highlightColor,
        public string $fontFamily,
        public float $fontSize = 13,
    ) {
    }

    public static function make(
        string $name,
        string $fgColor,
        string $bgColor,
        string $bgImage,
        string $highlightColor,
        string $fontFamily,
        float $fontSize,
    ): self {
        return new self(
            name: $name,
            fgColor: $fgColor,
            bgColor: $bgColor,
            bgImage: $bgImage,
            highlightColor: $highlightColor,
            fontFamily: $fontFamily,
            fontSize: $fontSize,
        );
    }
}
