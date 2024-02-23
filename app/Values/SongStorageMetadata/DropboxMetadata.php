<?php

namespace App\Values\SongStorageMetadata;

use App\Values\SongStorageMetadata\Contracts\SongStorageMetadata;

class DropboxMetadata implements SongStorageMetadata
{
    private function __construct(public string $path)
    {
    }

    public static function make(string $key): self
    {
        return new static($key);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
