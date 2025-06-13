<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Services\Scanner\FileScanner;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Services\SongStorages\Concerns\ScansUploadedFile;
use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;
use Throwable;

class SftpStorage extends SongStorage
{
    use DeletesUsingFilesystem;
    use ScansUploadedFile;

    private Filesystem $disk;

    public function __construct(protected FileScanner $scanner)
    {
        $this->disk = Storage::disk('sftp');
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        $result = $this->scanUploadedFile($this->scanner, $file, $uploader);
        $song = $this->scanner->getSong();
        $path = $this->generateRemotePath($file->getClientOriginalName(), $uploader);

        try {
            $this->disk->put($path, File::get($result->path));

            $song->update([
                'path' => "sftp://$path",
                'storage' => SongStorageType::SFTP,
            ]);

            return $song;
        } catch (Throwable $e) {
            throw SongUploadFailedException::fromThrowable($e);
        } finally {
            File::delete($result->path);
        }
    }

    public function delete(string $location, bool $backup = false): void
    {
        $this->deleteFileUnderPath(
            $location,
            static fn (Filesystem $fs, string $path) => $fs->copy($path, "$path.bak"),
        );
    }

    public function copyToLocal(string $path): string
    {
        $tmpDir = sys_get_temp_dir() . '/koel_tmp';
        File::ensureDirectoryExists($tmpDir);

        $localPath = $tmpDir . '/' . basename($path);

        File::put($localPath, $this->disk->get($path));

        return $localPath;
    }

    public function testSetup(): void
    {
        $this->disk->put('test.txt', 'Koel test file');
        $this->disk->delete('test.txt');
    }

    private function generateRemotePath(string $filename, User $uploader): string
    {
        return sprintf('%s__%s__%s', $uploader->id, Str::lower(Ulid::generate()), $filename);
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
