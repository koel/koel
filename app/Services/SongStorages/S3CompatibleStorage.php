<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\SongUploadFailedException;
use App\Models\User;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Services\SongStorages\Concerns\ReadsFileSizeUsingFilesystem;
use App\Services\SongStorages\Contracts\IssuesPresignedUploadUrls;
use App\Values\PresignedUpload;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3CompatibleStorage extends CloudStorage implements IssuesPresignedUploadUrls
{
    use DeletesUsingFilesystem;
    use ReadsFileSizeUsingFilesystem;

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
        return $this->getLocationPrefix() . Str::after($key, self::PENDING_UPLOAD_PREFIX);
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

    public function getFileSize(string $path): ?int
    {
        return self::readFileSizeUsingFilesystem(Storage::disk('s3'), $path);
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

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::S3;
    }

    protected function getLocationPrefix(): string
    {
        return "s3://$this->bucket/";
    }
}
