<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\User;
use App\Services\Scanner\FileScanner;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class S3CompatibleStorage extends CloudStorage
{
    use DeletesUsingFilesystem;

    public function __construct(protected FileScanner $scanner, private readonly ?string $bucket)
    {
        parent::__construct($scanner);
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        return DB::transaction(function () use ($file, $uploader): Song {
            $result = $this->scanUploadedFile($this->scanner, $file, $uploader);
            $song = $this->scanner->getSong();
            $key = $this->generateStorageKey($file->getClientOriginalName(), $uploader);

            $this->uploadToStorage($key, $result->path);

            $song->update([
                'path' => "s3://$this->bucket/$key",
                'storage' => SongStorageType::S3,
            ]);

            File::delete($result->path);

            return $song;
        });
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
        Storage::disk('s3')->put($key, File::get($path));
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
}
