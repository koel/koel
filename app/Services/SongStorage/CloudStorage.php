<?php

namespace App\Services\SongStorage;

use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Values\ScanConfiguration;
use App\Values\ScanResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

abstract class CloudStorage extends SongStorage
{
    public function __construct(protected FileScanner $scanner)
    {
        parent::__construct();
    }

    protected function scanUploadedFile(UploadedFile $file, User $uploader): ScanResult
    {
        // Can't scan the uploaded file directly, as it apparently causes some misbehavior during idv3 tag reading.
        // Instead, we copy the file to the tmp directory and scan it from there.
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'koel_tmp';
        File::ensureDirectoryExists($tmpDir);

        $tmpFile = $file->move($tmpDir, $file->getClientOriginalName());

        $result = $this->scanner->setFile($tmpFile)
            ->scan(ScanConfiguration::make(
                owner: $uploader,
                makePublic: $uploader->preferences->makeUploadsPublic
            ));

        throw_if($result->isError(), new SongUploadFailedException($result->error));

        return $result;
    }

    public function copyToLocal(Song $song): string
    {
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
