<?php

namespace App\Values;

use Illuminate\Support\Facades\File;

final class TranscodeResult
{
    public function __construct(public readonly string $path, public readonly string $checksum)
    {
    }

    public function valid(): bool
    {
        return File::isReadable($this->path) && File::hash($this->path) === $this->checksum;
    }
}
