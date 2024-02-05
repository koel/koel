<?php

namespace App\Services\SongStorage;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Models\User;
use Illuminate\Http\UploadedFile;

abstract class SongStorage
{
    public function __construct()
    {
        throw_unless(
            $this->supported(),
            new KoelPlusRequiredException('The storage driver is only supported in Koel Plus.')
        );
    }

    abstract protected function supported(): bool;

    abstract public function storeUploadedFile(UploadedFile $file, User $uploader): Song;
}
