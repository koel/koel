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
use RuntimeException;

class WebDAVStorage extends SongStorage implements MustDeleteTemporaryLocalFileAfterUpload
{
    use DeletesUsingFilesystem;
    use MovesUploadedFile;

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('webdav');
    }

    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        $path = $this->generateRemotePath(basename($uploadedFilePath), $uploader);
        // Buffer in memory so the PUT carries a fixed Content-Length; streaming PUTs trip
        // HTTP/2 INTERNAL_ERROR on Cloudflare-fronted NextCloud.
        $this->disk->put($path, File::get($uploadedFilePath));

        return UploadReference::make(location: "webdav://$path", localPath: $uploadedFilePath);
    }

    public function undoUpload(UploadReference $reference): void
    {
        File::delete($reference->localPath);
        $this->delete(location: Str::after($reference->location, 'webdav://'), backup: false);
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
            File::put($localPath, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }

        return $localPath;
    }

    public function testSetup(): void
    {
        $this->disk->put('test.txt', 'Koel test file');
        $this->disk->delete('test.txt');
    }

    public function getLocalPath(string $location): string
    {
        return $this->copyToLocal(Str::after($location, 'webdav://'));
    }

    public function deleteFileUnderPath(string $path, bool|Closure $backup): void
    {
        $this->deleteUsingFilesystem($this->disk, $path, $backup);
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::WEBDAV;
    }

    private function generateRemotePath(string $filename, User $uploader): string
    {
        return sprintf('%s__%s__%s', $uploader->id, Ulid::generate(), $filename);
    }
}
