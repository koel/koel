<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;

class TranscodeStrategyFactory
{
    public static function make(SongStorageType $storageType): TranscodingStrategy
    {
        return match ($storageType) {
            SongStorageType::LOCAL => app(LocalTranscodingStrategy::class),
            SongStorageType::S3,
            SongStorageType::S3_LAMBDA,
            SongStorageType::DROPBOX => app(CloudTranscodingStrategy::class),
            SongStorageType::SFTP => app(SftpTranscodingStrategy::class),
        };
    }
}
