<?php

namespace App\Services\SongStorage;

use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Values\ScanConfiguration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

class S3CompatibleStorage implements SongStorage
{
    public function __construct(private FileScanner $scanner, private string $bucket)
    {
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        // can't scan the uploaded file directly, as it may cause some misbehavior
        // instead, we copy the file to the tmp directory and scan it from there
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Str::uuid();
        File::makeDirectory($tmpDir);
        $tmpFile = $file->move($tmpDir, $file->getClientOriginalName());

        return DB::transaction(function () use ($tmpFile, $uploader) {
            $this->scanner->setFile($tmpFile)
                ->scan(ScanConfiguration::make(
                    owner: $uploader,
                    makePublic: $uploader->preferences->makeUploadsPublic
                ));

            $song = $this->scanner->getSong();
            $key = $this->generateStorageKey($tmpFile->getFilename(), $uploader);

            Storage::disk('s3')->put($key, $tmpFile->getContent());
            $song->update(['path' => "s3://$this->bucket/$key"]);

            File::delete($tmpFile->getRealPath());

            return $song;
        });
    }

    public function getSongPresignedUrl(Song $song): string
    {
        return Storage::disk('s3')->temporaryUrl($song->storage_metadata->getPath(), now()->addHour());
    }

    private function generateStorageKey(string $filename, User $uploader): string
    {
        return sprintf('%s__%s__%s', $uploader->id, Str::lower(Ulid::generate()), $filename);
    }
}
