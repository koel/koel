<?php

namespace App\Services\SongStorages;

use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Services\SongStorages\Concerns\ScansUploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

abstract class CloudStorage extends SongStorage
{
    use ScansUploadedFile;

    public function __construct(protected FileScanner $scanner)
    {
    }

    public function copyToLocal(Song $song): string
    {
        self::assertSupported();

        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'koel_tmp';
        File::ensureDirectoryExists($tmpDir);

        $publicUrl = $this->getSongPresignedUrl($song);
        $localPath = $tmpDir . DIRECTORY_SEPARATOR . basename($song->storage_metadata->getPath());

        File::copy($publicUrl, $localPath);

        return $localPath;
    }

    protected function generateStorageKey(string $filename, User $uploader): string
    {
        return sprintf('%s__%s__%s', $uploader->id, Str::lower(Ulid::generate()), $filename);
    }

    abstract public function getSongPresignedUrl(Song $song): string;
}
