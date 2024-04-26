<?php

namespace App\Services\SongStorages\Concerns;

use App\Models\Song;
use Illuminate\Contracts\Filesystem\Filesystem as IlluminateFilesystem;
use League\Flysystem\Filesystem;

trait DeletesUsingFilesystem
{
    private function deleteUsingFileSystem(Filesystem | IlluminateFilesystem $disk, Song $song, bool $backup): void
    {
        $path = $song->storage_metadata->getPath();

        if ($backup) {
            $disk->move($path, "backup/$path");
        }

        $disk->delete($path);
    }
}
