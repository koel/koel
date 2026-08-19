<?php

namespace App\Values;

final readonly class ProgressiveTranscodeSource
{
    private function __construct(
        public string $path,
        public bool $temporary,
    ) {}

    public static function make(string $path, bool $temporary = false): self
    {
        return new self($path, $temporary);
    }
}
