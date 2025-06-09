<?php

namespace App\Services\SongStorages\Concerns;

use Closure;
use Illuminate\Contracts\Filesystem\Filesystem as IlluminateFilesystem;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Filesystem;
use Throwable;

trait DeletesUsingFilesystem
{
    private function deleteUsingFilesystem(
        Filesystem | IlluminateFilesystem $disk,
        string $key,
        bool|Closure $backup,
    ): void {
        try {
            if (is_callable($backup)) {
                $backup($disk, $key);
            } elseif ($backup) {
                $disk->copy($key, "backup/$key.bak");
            }
        } catch (Throwable $e) {
            Log::error('Failed to backup file.', [
                'key' => $key,
                'error' => $e,
            ]);
        }

        $disk->delete($key);
    }
}
