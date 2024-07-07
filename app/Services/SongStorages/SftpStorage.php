<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Services\SongStorages\Concerns\ScansUploadedFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

final class SftpStorage extends SongStorage
{
    use DeletesUsingFilesystem;
    use ScansUploadedFile;

    public function __construct(protected FileScanner $scanner)
    {
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        self::assertSupported();

        return DB::transaction(function () use ($file, $uploader): Song {
            $result = $this->scanUploadedFile($this->scanner, $file, $uploader);
            $song = $this->scanner->getSong();

            $path = $this->generateRemotePath($file->getClientOriginalName(), $uploader);

            Storage::disk('sftp')->put($path, File::get($result->path));

            $song->update([
                'path' => "sftp://$path",
                'storage' => SongStorageType::SFTP,
            ]);

            File::delete($result->path);

            return $song;
        });
    }

    public function delete(Song $song, bool $backup = false): void
    {
        self::assertSupported();
        $this->deleteUsingFileSystem(Storage::disk('sftp'), $song, $backup);
    }

    public function getSongContent(Song $song): string
    {
        self::assertSupported();

        return Storage::disk('sftp')->get($song->storage_metadata->getPath());
    }

    public function copyToLocal(Song $song): string
    {
        self::assertSupported();

        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'koel_tmp';
        File::ensureDirectoryExists($tmpDir);

        $localPath = $tmpDir . DIRECTORY_SEPARATOR . basename($song->storage_metadata->getPath());

        File::put($localPath, $this->getSongContent($song));

        return $localPath;
    }

    private function generateRemotePath(string $filename, User $uploader): string
    {
        return sprintf('%s__%s__%s', $uploader->id, Str::lower(Ulid::generate()), $filename);
    }

    protected function getStorageType(): SongStorageType
    {
        return SongStorageType::SFTP;
    }
}
