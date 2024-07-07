<?php

namespace App\Enums;

use App\Facades\License;

enum SongStorageType: string
{
    case S3 = 's3';
    case S3_LAMBDA = 's3-lambda';
    case DROPBOX = 'dropbox';
    case SFTP = 'sftp';
    case LOCAL = '';

    public function supported(): bool
    {
        if (License::isPlus()) {
            return true;
        }

        return $this === self::LOCAL || $this === self::S3_LAMBDA;
    }
}
