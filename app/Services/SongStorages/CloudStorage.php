<?php

namespace App\Services\SongStorages;

use App\Helpers\Ulid;
use App\Models\User;
use App\Services\Scanner\FileScanner;
use App\Services\SongStorages\Concerns\ScansUploadedFile;
use Illuminate\Support\Facades\File;

abstract class CloudStorage extends SongStorage
{
    use ScansUploadedFile;

    public function __construct(protected FileScanner $scanner)
    {
    }

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
