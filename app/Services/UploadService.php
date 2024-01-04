<?php

namespace App\Services;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

use function Functional\memoize;

class UploadService
{
    public function __construct(private FileSynchronizer $fileSynchronizer)
    {
    }

    public function handleUploadedFile(UploadedFile $file, User $uploader): Song
    {
        $uploadDirectory = $this->getUploadDirectory($uploader);
        $targetFileName = $this->getTargetFileName($file, $uploader);

        $file->move($uploadDirectory, $targetFileName);
        $targetPathName = $uploadDirectory . $targetFileName;

        try {
            $result = $this->fileSynchronizer
                ->setOwner($uploader)
                ->setFile($targetPathName)
                ->sync();
        } catch (Throwable) {
            @unlink($targetPathName);
            throw new SongUploadFailedException('Unknown error');
        }

        if ($result->isError()) {
            @unlink($targetPathName);
            throw new SongUploadFailedException($result->error);
        }

        return $this->fileSynchronizer->getSong();
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

            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            return $dir;
        });
    }

    private function getTargetFileName(UploadedFile $file, User $uploader): string
    {
        // If there's no existing file with the same name in the upload directory, use the original name.
        // Otherwise, prefix the original name with a hash.
        // The whole point is to keep a readable file name when we can.
        if (!file_exists($this->getUploadDirectory($uploader) . $file->getClientOriginalName())) {
            return $file->getClientOriginalName();
        }

        return $this->getUniqueHash() . '_' . $file->getClientOriginalName();
    }

    private function getUniqueHash(): string
    {
        return substr(sha1(uniqid()), 0, 6);
    }
}
