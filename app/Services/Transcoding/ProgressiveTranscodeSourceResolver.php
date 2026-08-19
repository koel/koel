<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\SongStorages\CloudStorageFactory;
use App\Services\SongStorages\SftpStorage;
use App\Services\SongStorages\WebDAVStorage;
use App\Values\ProgressiveTranscodeSource;

class ProgressiveTranscodeSourceResolver
{
    public function resolve(Song $song): ProgressiveTranscodeSource
    {
        return match ($song->storage) {
            SongStorageType::LOCAL => ProgressiveTranscodeSource::make($song->path),
            SongStorageType::S3,
            SongStorageType::S3_LAMBDA,
            SongStorageType::DROPBOX,
                => ProgressiveTranscodeSource::make(
                CloudStorageFactory::make($song->storage)->getPresignedUrl($song->storage_metadata->getPath()),
            ),
            SongStorageType::SFTP => ProgressiveTranscodeSource::make(
                app(SftpStorage::class)->copyToLocal($song->storage_metadata->getPath()),
                temporary: true,
            ),
            SongStorageType::WEBDAV => ProgressiveTranscodeSource::make(
                app(WebDAVStorage::class)->copyToLocal($song->storage_metadata->getPath()),
                temporary: true,
            ),
        };
    }
}
