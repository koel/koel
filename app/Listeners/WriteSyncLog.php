<?php

namespace App\Listeners;

use App\Events\MediaSyncCompleted;
use App\Values\SyncResult;
use Illuminate\Support\Collection;
use Throwable;

class WriteSyncLog
{
    public function handle(MediaSyncCompleted $event): void
    {
        $transformer = static fn (SyncResult $entry) => (string) $entry;

        /** @var Collection $messages */
        $messages = config('koel.sync_log_level') === 'all'
            ? $event->results->map($transformer)
            : $event->results->error()->map($transformer);

        try {
            $file = storage_path('logs/sync-' . now()->format('Ymd-His') . '.log');
            file_put_contents($file, implode(PHP_EOL, $messages->toArray()));
        } catch (Throwable) {
        }
    }
}
