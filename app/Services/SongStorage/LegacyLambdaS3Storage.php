<?php

namespace App\Services\SongStorage;

use App\Exceptions\MethodNotImplementedException;
use App\Models\Song;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * The legacy storage implementation for Lambda and S3, to provide backward compatibility.
 * In this implementation, the songs are supposed to be uploaded to S3 directly.
 */
final class LegacyLambdaS3Storage extends S3CompatibleStorage
{
    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        throw new MethodNotImplementedException('Lambda storage does not support uploading.');
    }

    public function supported(): bool
    {
        return true;
    }
}
