<?php

namespace App\Services\SongStorages\Contracts;

use App\Models\User;
use App\Values\PresignedUpload;

interface IssuesPresignedUploadUrls
{
    public function presignUpload(string $fileName, User $uploader): PresignedUpload;

    public function ownsUploadKey(string $key, User $uploader): bool;

    public function locationFromKey(string $key): string;
}
