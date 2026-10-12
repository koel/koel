<?php

namespace App\Values\SongStorageMetadata;

use App\Enums\SongStorageType;
use Throwable;

abstract class SongStorageMetadata
{
    abstract public function getPath(): string;

    public static function fromLocation(SongStorageType $storage, string $location): self
    {
        try {
            switch ($storage) {
                case SongStorageType::SFTP:
                    preg_match('/^sftp:\\/\\/(.*)/', $location, $matches);
                    return SftpMetadata::make($matches[1]);

                case SongStorageType::S3:
                    preg_match('/^s3:\\/\\/([^\/]+)\\/(.+)/', $location, $matches);
                    return S3CompatibleMetadata::make($matches[1], $matches[2]);

                case SongStorageType::S3_LAMBDA:
                    preg_match('/^s3:\\/\\/([^\/]+)\\/(.+)/', $location, $matches);
                    return S3LambdaMetadata::make($matches[1], $matches[2]);

                case SongStorageType::DROPBOX:
                    preg_match('/^dropbox:\\/\\/(.*)/', $location, $matches);
                    return DropboxMetadata::make($matches[1]);

                case SongStorageType::WEBDAV:
                    preg_match('/^webdav:\\/\\/(.*)/', $location, $matches);
                    return WebDAVMetadata::make($matches[1]);

                default:
                    return LocalMetadata::make($location);
            }
        } catch (Throwable) {
            return LocalMetadata::make($location);
        }
    }
}
