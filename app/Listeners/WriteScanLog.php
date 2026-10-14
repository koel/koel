<?php

namespace App\Listeners;

use App\Enums\ScanResultType;
use App\Events\MediaScanCompleted;
use App\Models\ScannedFile;
use App\Repositories\ScannedFileRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

readonly class WriteScanLog
{
    private const DEFAULT_MAX_FILES = 30;

    public function __construct(
        private ScannedFileRepository $scannedFileRepository,
    ) {}

    public function handle(MediaScanCompleted $event): void
    {
        $type = config('koel.sync_log_level') === 'all' ? null : ScanResultType::ERROR;

        rescue(function () use ($event, $type): void {
            $file = storage_path('logs/sync-' . now()->format('Ymd-His') . '.log');

            $this->scannedFileRepository
                ->lazyGetByScanId($event->summary->scanId, $type)
                ->map(static fn (ScannedFile $scannedFile): string => (string) $scannedFile->toScanResult())
                ->chunk(1_000)
                ->each(static function (LazyCollection $lines, int $chunkIndex) use ($file): void {
                    if ($chunkIndex === 0) {
                        File::put($file, $lines->implode(PHP_EOL));
                    } else {
                        File::append($file, PHP_EOL . $lines->implode(PHP_EOL));
                    }
                });

            if (File::exists($file)) {
                self::pruneOldLogs();
            }
        });
    }

    private static function pruneOldLogs(): void
    {
        $maxFiles = filter_var(config('koel.scan_log_max_files'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'default' => self::DEFAULT_MAX_FILES],
        ]);

        if ($maxFiles === 0) {
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
