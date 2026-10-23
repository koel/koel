<?php

namespace App\Services\SongStorages\Concerns;

use Illuminate\Contracts\Filesystem\Filesystem as IlluminateFilesystem;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToRetrieveMetadata;

trait ReadsFileSizeUsingFilesystem
{
    private static function readFileSizeUsingFilesystem(Filesystem|IlluminateFilesystem $disk, string $key): ?int
    {
        try {
            return $disk instanceof Filesystem ? $disk->fileSize($key) : $disk->size($key);
        } catch (UnableToRetrieveMetadata) {
            return null;
        }
    }
}
