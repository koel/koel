<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use Illuminate\Support\Facades\File;

class WebDAVStorage extends RemoteDiskStorage
{
    protected function getDiskName(): string
    {
        return 'webdav';
    }

    protected function putFile(string $remotePath, string $localPath): void
    {
        // Buffer in memory so the PUT carries a fixed Content-Length; streaming PUTs trip
        // HTTP/2 INTERNAL_ERROR on Cloudflare-fronted NextCloud.
        $this->disk->put($remotePath, File::get($localPath));
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::WEBDAV;
    }
}
