<?php

namespace App\Jobs;

use App\Services\SongStorages\SongStorage;
use App\Values\Song\SongFileInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteSongFilesJob extends QueuedJob
{
    /**
     * @param Collection<SongFileInfo>|array<array-key, SongFileInfo> $files
     */
    public function __construct(public readonly Collection $files)
    {
    }

    public function handle(SongStorage $storage): void
    {
        $this->files->each(static function (SongFileInfo $file) use ($storage): void {
            try {
                $storage->delete($file->location, config('koel.backup_on_delete'));
            } catch (Throwable $e) {
                Log::error('Failed to remove song file', [
                    'path' => $file->location,
                    'exception' => $e,
                ]);
            }
        });
    }
}
