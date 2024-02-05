<?php

namespace App\Services\SongStorage;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Values\ScanConfiguration;
use App\Values\SongStorageTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Throwable;

use function Functional\memoize;

final class LocalStorage extends SongStorage
{
    public function __construct(private FileScanner $scanner)
    {
        parent::__construct();
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        $uploadDirectory = $this->getUploadDirectory($uploader);
        $targetFileName = $this->getTargetFileName($file, $uploader);

        $file->move($uploadDirectory, $targetFileName);
        $targetPathName = $uploadDirectory . $targetFileName;

        try {
            $result = $this->scanner->setFile($targetPathName)
                ->scan(ScanConfiguration::make(
                    owner: $uploader,
                    makePublic: $uploader->preferences->makeUploadsPublic
                ));
        } catch (Throwable $e) {
            File::delete($targetPathName);
            throw new SongUploadFailedException($e->getMessage());
        }

        if ($result->isError()) {
            File::delete($targetPathName);
            throw new SongUploadFailedException($result->error);
        }

        return $this->scanner->getSong();
    }

    private function getUploadDirectory(User $uploader): string
    {
        return memoize(static function () use ($uploader): string {
            $mediaPath = Setting::get('media_path');

            throw_unless((bool) $mediaPath, MediaPathNotSetException::class);

            $dir = sprintf(
                '%s%s__KOEL_UPLOADS_$%s__%s',
                $mediaPath,
                DIRECTORY_SEPARATOR,
                $uploader->id,
                DIRECTORY_SEPARATOR
            );

            File::ensureDirectoryExists($dir);

            return $dir;
        });
    }

    private function getTargetFileName(UploadedFile $file, User $uploader): string
    {
        // If there's no existing file with the same name in the upload directory, use the original name.
        // Otherwise, prefix the original name with a hash.
        // The whole point is to keep a readable file name when we can.
        if (!File::exists($this->getUploadDirectory($uploader) . $file->getClientOriginalName())) {
            return $file->getClientOriginalName();
        }

        return $this->getUniqueHash() . '_' . $file->getClientOriginalName();
    }

    private function getUniqueHash(): string
    {
        return substr(sha1(uniqid()), 0, 6);
    }

    public function supported(): bool
    {
        return SongStorageTypes::supported(SongStorageTypes::LOCAL);
    }
}
