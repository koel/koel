<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\NonCloudStorageException;

class CloudStorageFactory
{
    public static function make(SongStorageType $storageType): CloudStorage
    {
        return match ($storageType) {
            SongStorageType::S3_LAMBDA => app(S3LambdaStorage::class),
            SongStorageType::S3 => app(S3CompatibleStorage::class),
            SongStorageType::DROPBOX => app(DropboxStorage::class),
            default => throw NonCloudStorageException::create($storageType),
        };
    }
}
