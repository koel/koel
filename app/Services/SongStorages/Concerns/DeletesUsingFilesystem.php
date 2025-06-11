<?php

namespace App\Services\SongStorages\Concerns;

use App\Models\Song;
use Closure;
use Illuminate\Contracts\Filesystem\Filesystem as IlluminateFilesystem;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Filesystem;
use Throwable;

trait DeletesUsingFilesystem
{
    private function deleteUsingFileSystem(
        Filesystem | IlluminateFilesystem $disk,
        Song $song,
        Closure|bool $backup
    ): void {
        $path = $song->storage_metadata->getPath();

        try {
            if (is_callable($backup)) {
                $backup($disk, $path);
            } elseif ($backup) {
                $disk->copy($path, "backup/$path");
            }
        } catch (Throwable $e) {
            Log::error('Cannot backup song file', [
                'song_id' => $song->id,
                'path' => $path,
                'error' => $e,
            ]);
        }

        $disk->delete($path);
    }
}
