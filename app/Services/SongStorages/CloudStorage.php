<?php

namespace App\Services\SongStorages;

use App\Models\User;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Values\UploadReference;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class CloudStorage extends SongStorage implements MustDeleteTemporaryLocalFileAfterUpload
{
    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        $key = $this->generateStorageKey(basename($uploadedFilePath), $uploader);
        $this->uploadToStorage($key, $uploadedFilePath);

        return UploadReference::make(
            location: $this->getLocationPrefix() . $key,
            localPath: $uploadedFilePath,
            originalFileName: basename($uploadedFilePath),
        );
    }

    public function undoUpload(UploadReference $reference): void
    {
        File::delete($reference->localPath);

        $this->deleteFileWithKey($this->getKeyFromLocation($reference->location), backup: false);
    }

    public function getLocalPath(string $location): string
    {
        return $this->copyToLocal($this->getKeyFromLocation($location));
    }

    public function copyToLocal(string $key): string
    {
        $publicUrl = $this->getPresignedUrl($key);
        $localPath = $this->generateTemporaryLocalPath($key);

        File::copy($publicUrl, $localPath);

        return $localPath;
    }

    abstract public function uploadToStorage(string $key, string $path): void;

    abstract public function getPresignedUrl(string $key): string;

    abstract public function deleteFileWithKey(string $key, bool $backup): void;

    abstract protected function getLocationPrefix(): string;

    private function getKeyFromLocation(string $location): string
    {
        return Str::after($location, $this->getLocationPrefix());
    }
}
