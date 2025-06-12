<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Values\ScanConfiguration;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

use function Functional\memoize;

class LocalStorage extends SongStorage
{
    public function __construct(private readonly FileScanner $scanner)
    {
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
            throw SongUploadFailedException::fromThrowable($e);
        }

        if ($result->isError()) {
            File::delete($targetPathName);
            throw SongUploadFailedException::fromErrorMessage($result->error);
        }

        return $this->scanner->getSong();
    }

    private function getUploadDirectory(User $uploader): string
    {
        return memoize(static function () use ($uploader): string {
            $mediaPath = Setting::get('media_path');

            throw_unless((bool) $mediaPath, MediaPathNotSetException::class);

            $dir = "$mediaPath/__KOEL_UPLOADS_\${$uploader->id}__/";
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
        return Str::take(sha1(Str::uuid()), 6);
    }

    public function delete(string $location, bool $backup = false): void
    {
        if ($backup) {
            File::move($location, "$location.bak");
        }

        throw_unless(File::delete($location), new Exception("Failed to delete song file: $location"));
    }

    public function testSetup(): void
    {
        $mediaPath = Setting::get('media_path');

        if (File::isReadable($mediaPath) && File::isWritable($mediaPath)) {
            return;
        }

        throw new Exception("The media path $mediaPath is not readable or writable.");
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::LOCAL;
    }
}
