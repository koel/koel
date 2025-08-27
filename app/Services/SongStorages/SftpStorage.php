<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\User;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Services\SongStorages\Concerns\MovesUploadedFile;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Values\UploadReference;
use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SftpStorage extends SongStorage implements MustDeleteTemporaryLocalFileAfterUpload
{
    use DeletesUsingFilesystem;
    use MovesUploadedFile;

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('sftp');
    }

    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        $path = $this->generateRemotePath(basename($uploadedFilePath), $uploader);
        $this->disk->put($path, fopen($uploadedFilePath, 'r'));

        return UploadReference::make(
            location: "sftp://$path",
            localPath: $uploadedFilePath,
        );
    }

    public function undoUpload(UploadReference $reference): void
    {
        // Delete the tmp file
        File::delete($reference->localPath);

        // Delete the file from the SFTP server
        $this->delete(location: Str::after($reference->location, 'sftp://'), backup: false);
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

        file_put_contents($localPath, $this->disk->readStream($path));

        return $localPath;
    }

    public function testSetup(): void
    {
        $this->disk->put('test.txt', 'Koel test file');
        $this->disk->delete('test.txt');
    }

    private function generateRemotePath(string $filename, User $uploader): string
    {
        return sprintf('%s__%s__%s', $uploader->id, Ulid::generate(), $filename);
    }

    public function deleteFileUnderPath(string $path, bool|Closure $backup): void
    {
        $this->deleteUsingFilesystem($this->disk, $path, $backup);
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::SFTP;
    }
}
