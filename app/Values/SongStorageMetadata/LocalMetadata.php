<?php

namespace App\Values\SongStorageMetadata;

use App\Values\SongStorageMetadata\Contracts\SongStorageMetadata;

final class LocalMetadata implements SongStorageMetadata
{
    private function __construct(public string $path)
    {
    }

    public static function make(string $path): self
    {
        return new self($path);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
