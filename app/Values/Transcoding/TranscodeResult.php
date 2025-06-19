<?php

namespace App\Values\Transcoding;

use Illuminate\Support\Facades\File;

final readonly class TranscodeResult
{
    public function __construct(public string $path, public string $checksum)
    {
    }

    public function valid(): bool
    {
        return File::isReadable($this->path) && File::hash($this->path) === $this->checksum;
    }
}
