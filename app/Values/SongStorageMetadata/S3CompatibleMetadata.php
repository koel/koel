<?php

namespace App\Values\SongStorageMetadata;

class S3CompatibleMetadata extends SongStorageMetadata
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
}
