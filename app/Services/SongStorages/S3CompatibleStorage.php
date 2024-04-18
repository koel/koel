<?php

namespace App\Services\SongStorages;

use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Values\SongStorageTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class S3CompatibleStorage extends CloudStorage
{
    public function __construct(protected FileScanner $scanner, private string $bucket)
    {
        parent::__construct($scanner);
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        self::assertSupported();

        return DB::transaction(function () use ($file, $uploader): Song {
            $result = $this->scanUploadedFile($file, $uploader);
            $song = $this->scanner->getSong();
            $key = $this->generateStorageKey($file->getClientOriginalName(), $uploader);

            Storage::disk('s3')->put($key, File::get($result->path));

            $song->update([
                'path' => "s3://$this->bucket/$key",
                'storage' => SongStorageTypes::S3,
            ]);

            File::delete($result->path);

            return $song;
        });
    }

    public function getSongPresignedUrl(Song $song): string
    {
        self::assertSupported();

        return Storage::disk('s3')->temporaryUrl($song->storage_metadata->getPath(), now()->addHour());
    }

    public function delete(Song $song, bool $backup = false): void
    {
        self::assertSupported();

        $disk = Storage::disk('s3');
        $path = $song->storage_metadata->getPath();

        if ($backup) {
            $disk->move($path, "backup/$path");
        }

        $disk->delete($song->storage_metadata->getPath());
    }

    public function testSetup(): void
    {
        Storage::disk('s3')->put('test.txt', 'Koel test file');
        Storage::disk('s3')->delete('test.txt');
    }

    public function supported(): bool
    {
        return SongStorageTypes::supported(SongStorageTypes::S3);
    }
}
