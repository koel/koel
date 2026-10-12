<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Services\SongStorages\SftpStorage;
use App\Services\SongStorages\WebDAVStorage;

class TranscodeStrategyFactory
{
    public static function make(SongStorageType $storageType): TranscodingStrategy
    {
        return match ($storageType) {
            SongStorageType::LOCAL => app(LocalTranscodingStrategy::class),
            SongStorageType::S3,
            SongStorageType::S3_LAMBDA,
            SongStorageType::DROPBOX,
                => app(CloudTranscodingStrategy::class),
            SongStorageType::SFTP => app(RemoteDiskTranscodingStrategy::class, ['storage' => app(SftpStorage::class)]),
            SongStorageType::WEBDAV => app(RemoteDiskTranscodingStrategy::class, [
                'storage' => app(WebDAVStorage::class),
            ]),
        };
    }
}
