<?php

namespace App\Values;

final readonly class GenreSummary
{
    private function __construct(
        public string $publicId,
        public string $name,
        public int $songCount,
        public float $length
    ) {
    }

    public static function make(string $publicId, string $name, int $songCount, float $length): self
    {
        return new self(
            publicId: $publicId,
            name: $name,
            songCount: $songCount,
            length: $length,
        );
    }
}
