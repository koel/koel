<?php

namespace App\Listeners;

use App\Events\MediaScanCompleted;
use App\Values\ScanResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class WriteSyncLog
{
    public function handle(MediaScanCompleted $event): void
    {
        $transformer = static fn (ScanResult $entry) => (string) $entry;

        /** @var Collection $messages */
        $messages = config('koel.sync_log_level') === 'all'
            ? $event->results->map($transformer)
            : $event->results->error()->map($transformer);

        attempt(static function () use ($messages): void {
            $file = storage_path('logs/sync-' . now()->format('Ymd-His') . '.log');
            File::put($file, implode(PHP_EOL, $messages->toArray()));
        });
    }
}
