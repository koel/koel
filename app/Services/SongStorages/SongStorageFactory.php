<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;

class SongStorageFactory
{
    public static function make(SongStorageType $storageType): SongStorage
    {
        return match ($storageType) {
            SongStorageType::LOCAL => app(LocalStorage::class),
            SongStorageType::SFTP => app(SftpStorage::class),
            SongStorageType::S3 => app(S3CompatibleStorage::class),
            SongStorageType::S3_LAMBDA => app(S3LambdaStorage::class),
            SongStorageType::DROPBOX => app(DropboxStorage::class),
        };
    }
}
