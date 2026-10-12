<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;

class SftpStorage extends RemoteDiskStorage
{
    protected function getDiskName(): string
    {
        return 'sftp';
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::SFTP;
    }
}
