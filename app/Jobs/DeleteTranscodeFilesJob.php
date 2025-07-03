<?php

namespace App\Jobs;

use App\Services\Transcoding\TranscodeStrategyFactory;
use App\Values\Transcoding\TranscodeFileInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteTranscodeFilesJob extends QueuedJob
{
    /**
     * @param Collection<TranscodeFileInfo>|array<array-key, TranscodeFileInfo> $files
     */
    public function __construct(public readonly Collection $files)
    {
    }

    public function handle(): void
    {
        $this->files->each(static function (TranscodeFileInfo $file): void {
            try {
                TranscodeStrategyFactory::make($file->storage)->deleteTranscodeFile($file->location, $file->storage);
            } catch (Throwable $e) {
                if (app()->runningUnitTests()) {
                    return;
                }

                Log::error('Failed to remove transcode file', [
                    'location' => $file->location,
                    'storage' => $file->storage,
                    'exception' => $e,
                ]);
            }
        });
    }
}
