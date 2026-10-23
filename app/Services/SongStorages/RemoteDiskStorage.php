<?php

namespace App\Services\SongStorages;

use App\Models\User;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Services\SongStorages\Concerns\ReadsFileSizeUsingFilesystem;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Values\UploadReference;
use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

// @mago-ignore lint:too-many-methods
abstract class RemoteDiskStorage extends SongStorage implements MustDeleteTemporaryLocalFileAfterUpload
{
    private const int LOCAL_COPY_LOCK_SECONDS = 600;
    private const int LOCAL_COPY_LOCK_WAIT_SECONDS = 120;

    use DeletesUsingFilesystem;
    use ReadsFileSizeUsingFilesystem;

    protected Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk($this->getDiskName());
    }

    abstract protected function getDiskName(): string;

    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        $path = $this->generateStorageKey(basename($uploadedFilePath), $uploader);
        $this->putFile($path, $uploadedFilePath);

        return UploadReference::make(
            location: $this->getLocationPrefix() . $path,
            localPath: $uploadedFilePath,
            originalFileName: basename($uploadedFilePath),
        );
    }

    protected function putFile(string $remotePath, string $localPath): void
    {
        $stream = fopen($localPath, 'r');

        try {
            $this->disk->put($remotePath, $stream);
        } finally {
            fclose($stream);
        }
    }

    public function undoUpload(UploadReference $reference): void
    {
        File::delete($reference->localPath);

        $this->delete(location: $this->stripLocationPrefix($reference->location), backup: false);
    }

    public function delete(string $location, bool $backup = false): void
    {
        $this->deleteFileUnderPath(
            $location,
            $backup ? static fn (Filesystem $fs, string $path) => $fs->copy($path, "$path.bak") : false,
        );
    }

    public function copyToLocal(string $path): string
    {
        $localPath = $this->generateTemporaryLocalPath($path);
        $stream = $this->disk->readStream($path);

        throw_unless($stream, new RuntimeException("Failed to open remote stream for $path."));

        try {
            $bytes = file_put_contents($localPath, $stream);
        } finally {
            fclose($stream);
        }

        if ($bytes === false) {
            File::delete($localPath);

            throw new RuntimeException("Failed to write remote stream to $localPath.");
        }

        return $localPath;
    }

    public function getReusableLocalCopy(string $path): string
    {
        return Cache::lock(
            cache_key('reusable local copy', $this->getDiskName(), $path),
            self::LOCAL_COPY_LOCK_SECONDS,
        )->block(self::LOCAL_COPY_LOCK_WAIT_SECONDS, fn (): string => $this->findOrCreateReusableLocalCopy($path));
    }

    private function findOrCreateReusableLocalCopy(string $path): string
    {
        $reusablePath = artifact_path(sprintf(
            'tmp/stream_%s_%s',
            sha1($this->getDiskName() . ':' . $path),
            basename($path),
        ));

        if (File::exists($reusablePath)) {
            touch($reusablePath);

            return $reusablePath;
        }

        File::move($this->copyToLocal($path), $reusablePath);

        return $reusablePath;
    }

    public function getFileSize(string $path): ?int
    {
        return self::readFileSizeUsingFilesystem($this->disk, $path);
    }

    public function testSetup(): void
    {
        $this->disk->put('test.txt', 'Koel test file');
        $this->disk->delete('test.txt');
    }

    public function deleteFileUnderPath(string $path, bool|Closure $backup): void
    {
        $this->deleteUsingFilesystem($this->disk, $path, $backup);
    }

    public function getLocalPath(string $location): string
    {
        return $this->copyToLocal($this->stripLocationPrefix($location));
    }

    private function getLocationPrefix(): string
    {
        return "{$this->getDiskName()}://";
    }

    private function stripLocationPrefix(string $location): string
    {
        return Str::after($location, $this->getLocationPrefix());
    }
}
