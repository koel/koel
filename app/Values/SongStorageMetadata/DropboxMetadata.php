<?php

namespace App\Values\SongStorageMetadata;

class DropboxMetadata implements SongStorageMetadata
{
    private function __construct(public string $appFolder, public string $key)
    {
    }

    public static function make(string $appFolder, string $key): self
    {
        return new static($appFolder, $key);
    }

    public function getPath(): string
    {
        return $this->key;
    }
}
