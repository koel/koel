<?php

namespace App\Values;

final class Genre
{
    public const NO_GENRE = 'No Genre';

    private function __construct(public string $name, public int $songCount, public float $length)
    {
    }

    public static function make(string $name, int $songCount, float $length): self
    {
        return new self(name: $name, songCount: $songCount, length: $length);
    }
}
