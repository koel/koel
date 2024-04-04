<?php

namespace App\Services\SongStorages;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Models\User;
use Illuminate\Http\UploadedFile;

abstract class SongStorage
{
    abstract public function storeUploadedFile(UploadedFile $file, User $uploader): Song;

    abstract public function delete(Song $song, bool $backup = false): void;

    abstract protected function supported(): bool;

    protected function assertSupported(): void
    {
        throw_unless(
            $this->supported(),
            new KoelPlusRequiredException('The storage driver is only supported in Koel Plus.')
        );
    }
}
