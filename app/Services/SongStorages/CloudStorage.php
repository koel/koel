<?php

namespace App\Services\SongStorages;

use App\Helpers\Ulid;
use App\Models\User;
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

    protected function generateStorageKey(string $filename, User $uploader): string
    {
        return sprintf('%s__%s__%s', $uploader->id, Ulid::generate(), $filename);
    }

    abstract public function uploadToStorage(string $key, string $path): void;

    abstract public function getPresignedUrl(string $key): string;

    abstract public function deleteFileWithKey(string $key, bool $backup): void;
}
