<?php

namespace App\Services\SongStorages;

use App\Helpers\Ulid;
use App\Services\SongStorages\Concerns\MovesUploadedFile;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use Illuminate\Support\Facades\File;

abstract class CloudStorage extends SongStorage implements MustDeleteTemporaryLocalFileAfterUpload
{
    use MovesUploadedFile;

    public function copyToLocal(string $key): string
    {
        $publicUrl = $this->getPresignedUrl($key);
        $localPath = artifact_path(sprintf('tmp/%s_%s', Ulid::generate(), basename($key)));

        File::copy($publicUrl, $localPath);

        return $localPath;
    }

    abstract public function uploadToStorage(string $key, string $path): void;

    abstract public function getPresignedUrl(string $key): string;

    abstract public function deleteFileWithKey(string $key, bool $backup): void;
}
