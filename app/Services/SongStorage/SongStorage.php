<?php

namespace App\Services\SongStorage;

use App\Models\Song;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface SongStorage
{
    public function storeUploadedFile(UploadedFile $file, User $uploader): Song;
}
