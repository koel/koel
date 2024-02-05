<?php

namespace App\Values\SongStorageMetadata;

use App\Facades\License;

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

    public function supported(): bool
    {
        return License::isPlus();
    }
}
