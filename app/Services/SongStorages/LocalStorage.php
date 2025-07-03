<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\MediaPathNotSetException;
use App\Helpers\Ulid;
use App\Models\Setting;
use App\Models\User;
use App\Values\UploadReference;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LocalStorage extends SongStorage
{
    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        $destination = $this->getDestination($uploadedFilePath, $uploader);
        File::move($uploadedFilePath, $destination);

        // For local storage, the "location" and "localPath" are the same.
        return UploadReference::make(
            location: $destination,
            localPath: $destination,
        );
    }

    public function undoUpload(UploadReference $reference): void
    {
        // To undo an upload, we simply delete the file from the local disk.
        File::delete($reference->localPath);
    }

    private function getUploadDirectory(User $uploader): string
    {
        $mediaPath = Setting::get('media_path');

        throw_unless((bool) $mediaPath, MediaPathNotSetException::class);

        $dir = "$mediaPath/__KOEL_UPLOADS_\${$uploader->id}__/";
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    private function getDestination(string $sourcePath, User $uploader): string
    {
        $uploadDirectory = $this->getUploadDirectory($uploader);
        $sourceName = basename($sourcePath);

        // If there's no existing file with the same name in the upload directory, use the original name.
        // Otherwise, prefix the original name with a hash.
        // The whole point is to keep a readable file name when we can.
        if (!File::exists($uploadDirectory . $sourceName)) {
            return $uploadDirectory . $sourceName;
        }

        return $uploadDirectory . $this->getUniqueHash() . '_' . $sourceName;
    }

    private function getUniqueHash(): string
    {
        return Str::take(sha1(Ulid::generate()), 6);
    }

    public function delete(string $location, bool $backup = false): void
    {
        if ($backup) {
            File::move($location, "$location.bak");
        } else {
            throw_unless(File::delete($location), new Exception("Failed to delete song file: $location"));
        }
    }

    public function testSetup(): void
    {
        $mediaPath = Setting::get('media_path');

        if (File::isReadable($mediaPath) && File::isWritable($mediaPath)) {
            return;
        }

        throw new Exception("The media path $mediaPath is not readable or writable.");
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::LOCAL;
    }
}
