<?php

namespace App\Services\SongStorages;

use App\Helpers\Ulid;
use App\Models\User;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Values\UploadReference;
use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

abstract class RemoteDiskStorage extends SongStorage implements MustDeleteTemporaryLocalFileAfterUpload
{
    use DeletesUsingFilesystem;

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

        return UploadReference::make(location: $this->getLocationPrefix() . $path, localPath: $uploadedFilePath);
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
        $localPath = artifact_path(sprintf('tmp/%s_%s', Ulid::generate(), basename($path)));
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
