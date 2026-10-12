<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\SongUploadFailedException;
use App\Models\User;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Services\SongStorages\Contracts\IssuesPresignedUploadUrls;
use App\Values\PresignedUpload;
use App\Values\UploadReference;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// @mago-ignore lint:too-many-methods
class S3CompatibleStorage extends CloudStorage implements IssuesPresignedUploadUrls
{
    use DeletesUsingFilesystem;

    private const string PENDING_UPLOAD_PREFIX = 'pending/';

    public function __construct(
        private readonly S3UploadUrlSigner $uploadUrlSigner,
        #[Config('filesystems.disks.s3.bucket')]
        private readonly ?string $bucket = null,
    ) {}

    public function presignUpload(string $fileName, int $fileSize, User $uploader): PresignedUpload
    {
        return $this->uploadUrlSigner->sign(
            key: self::PENDING_UPLOAD_PREFIX . $this->generateStorageKey($fileName, $uploader),
            size: $fileSize,
            expiresAt: Carbon::now()->addHour(),
        );
    }

    public function ownsUploadKey(string $key, User $uploader): bool
    {
        if (!Str::startsWith($key, self::PENDING_UPLOAD_PREFIX)) {
            return false;
        }

        $storageKey = Str::after($key, self::PENDING_UPLOAD_PREFIX);

        if (Str::contains($storageKey, ['/', '\\'])) {
            return false;
        }

        return Str::startsWith($storageKey, "{$uploader->public_id}__");
    }

    public function moveUploadOutOfPending(string $key): void
    {
        $storageKey = Str::after($key, self::PENDING_UPLOAD_PREFIX);
        $moved = rescue(static fn (): bool => Storage::disk('s3')->move($key, $storageKey), false);

        throw_unless($moved, SongUploadFailedException::make('The uploaded file could not be stored.'));
    }

    public function sizeOfUpload(string $key): int
    {
        return Storage::disk('s3')->size($key);
    }

    public function locationFromKey(string $key): string
    {
        return "s3://$this->bucket/" . Str::after($key, self::PENDING_UPLOAD_PREFIX);
    }

    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        $key = $this->generateStorageKey(basename($uploadedFilePath), $uploader);
        $this->uploadToStorage($key, $uploadedFilePath);

        return UploadReference::make(location: "s3://$this->bucket/$key", localPath: $uploadedFilePath);
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
        Storage::disk('s3')->put($key, fopen($path, 'r'));
    }

    public function testSetup(): void
    {
        Storage::disk('s3')->put('test.txt', 'Koel test file');
        Storage::disk('s3')->delete('test.txt');
    }

    public function getLocalPath(string $location): string
    {
        return $this->copyToLocal(Str::after($location, "s3://$this->bucket/"));
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::S3;
    }
}
