<?php

namespace App\Values\SongStorageMetadata;

use App\Facades\License;

class S3CompatibleMetadata implements SongStorageMetadata
{
    private function __construct(public string $bucket, public string $key)
    {
    }

    public static function make(string $bucket, string $key): self
    {
        return new static($bucket, $key);
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
