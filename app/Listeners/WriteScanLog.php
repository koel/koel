<?php

namespace App\Listeners;

use App\Events\MediaScanCompleted;
use App\Values\Scanning\ScanResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

readonly class WriteScanLog implements ShouldQueue
{
    private const DEFAULT_MAX_FILES = 30;

    public function handle(MediaScanCompleted $event): void
    {
        $transformer = static fn (ScanResult $entry) => (string) $entry;

        /** @var Collection $messages */
        $messages = config('koel.sync_log_level') === 'all'
            ? $event->results->map($transformer)
            : $event->results->error()->map($transformer);

        if ($messages->isEmpty()) {
            return;
        }

        rescue(static function () use ($messages): void {
            $file = storage_path('logs/sync-' . now()->format('Ymd-His') . '.log');
            File::put($file, implode(PHP_EOL, $messages->toArray()));

            self::pruneOldLogs();
        });
    }

    private static function pruneOldLogs(): void
    {
        $maxFiles = filter_var(config('koel.scan_log_max_files'), FILTER_VALIDATE_INT);

        if ($maxFiles === false) {
            $maxFiles = self::DEFAULT_MAX_FILES;
        }

        if ($maxFiles <= 0) {
            return;
        }

        $excess = collect(File::glob(storage_path('logs/sync-*.log')) ?: [])
            ->sort()
            ->reverse()
            ->slice($maxFiles)
            ->values()
            ->all();

        if ($excess && !File::delete($excess)) {
            Log::warning('Could not prune every old scan log under storage/logs; check their ownership.');
        }
    }
}
