<?php

namespace App\Values\SongStorageMetadata;

final class SftpMetadata extends SongStorageMetadata
{
    private function __construct(public string $path)
    {
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
