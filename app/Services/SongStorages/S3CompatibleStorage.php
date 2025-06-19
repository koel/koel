<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Models\User;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Values\UploadReference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3CompatibleStorage extends CloudStorage
{
    use DeletesUsingFilesystem;

    public function __construct(private readonly ?string $bucket = null)
    {
    }

    public function storeUploadedFile(UploadedFile $uploadedFile, User $uploader): UploadReference
    {
        $file = $this->moveUploadedFileToTemporaryLocation($uploadedFile);
        $key = $this->generateStorageKey($uploadedFile->getClientOriginalName(), $uploader);

        $this->uploadToStorage($key, $file->getRealPath());

        return UploadReference::make(
            location: "s3://$this->bucket/$key",
            localPath: $file->getRealPath(),
        );
    }

    public function undoUpload(UploadReference $reference): void
    {
        // Delete the temporary file
        File::delete($reference->localPath);

        // Delete the file from S3
        $this->deleteFileWithKey(Str::after($reference->location, "s3://$this->bucket/"));
    }

    public function getPresignedUrl(string $key): string
    {
        return Storage::disk('s3')->temporaryUrl($key, now()->addHour());
    }

    public function deleteFileWithKey(string $key, bool $backup = false): void
    {
        $this->deleteUsingFilesystem(Storage::disk('s3'), $key, $backup);
    }

    public function delete(string $location, bool $backup = false): void
    {
        $this->deleteFileWithKey($location, $backup);
    }

    public function uploadToStorage(string $key, string $path): void
    {
        Storage::disk('s3')->put($key, File::get($path));
    }

    public function testSetup(): void
    {
        Storage::disk('s3')->put('test.txt', 'Koel test file');
        Storage::disk('s3')->delete('test.txt');
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::S3;
    }
}
