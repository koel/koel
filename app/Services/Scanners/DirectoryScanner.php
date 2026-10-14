<?php

namespace App\Services\Scanners;

use App\Enums\ScanEvent;
use App\Events\MediaScanCompleted;
use App\Services\Scanners\Strategies\ParallelScanStrategy;
use App\Services\Scanners\Strategies\SequentialScanStrategy;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanSummary;
use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DirectoryScanner extends Scanner
{
    /** @var array<string, callable> */
    private array $events = [];

    public function __construct(
        IndividualFileHandler $fileHandler,
        Finder $finder,
        private readonly SequentialScanStrategy $sequentialStrategy,
        private readonly ParallelScanStrategy $parallelStrategy,
        private readonly ScanIndexer $indexer,
        private readonly ScannedFileService $scannedFileService,
    ) {
        parent::__construct($fileHandler, $finder);
    }

    public function scan(string $directory, ScanConfiguration $config, int $jobs = 1): ScanSummary
    {
        self::setSystemRequirements();

        $pathList = tempnam(sys_get_temp_dir(), 'koel_scan_');
        $recorder = $this->scannedFileService->startScan();

        try {
            $pathCount = $this->writePathList($directory, $pathList);

            if (isset($this->events[ScanEvent::PATHS_GATHERED->name])) {
                $this->events[ScanEvent::PATHS_GATHERED->name]($pathCount);
            }

            $onResult = function (ScanResult $result) use ($recorder): void {
                $recorder->record($result);

                if (isset($this->events[ScanEvent::SCAN_PROGRESS->name])) {
                    $this->events[ScanEvent::SCAN_PROGRESS->name]($result);
                }
            };

            $this->indexer->withoutSyncingToSearch(fn () => $jobs > 1
                ? $this->parallelStrategy->scan($pathList, $pathCount, $config, $jobs, $onResult)
                : $this->sequentialStrategy->scan(File::lines($pathList)->filter(), $config, $onResult));

            $summary = $recorder->finish();

            $this->indexer->reindex($summary->scanId);

            event(new MediaScanCompleted($summary));

            return $summary;
        } finally {
            File::delete($pathList);
            $this->scannedFileService->deleteScannedFiles($recorder->scanId);
        }
    }

    public function on(ScanEvent $event, callable $callback): void
    {
        $this->events[$event->name] = $callback;
    }

    private function writePathList(string $directory, string $pathList): int
    {
        $pathCount = 0;

        LazyCollection::make(fn () => yield from $this->gatherFiles($directory))
            ->map(static fn (SplFileInfo $file): string => $file->getRealPath())
            ->chunk(1_000)
            ->each(static function (LazyCollection $paths) use ($pathList, &$pathCount): void {
                File::append($pathList, $paths->implode(PHP_EOL) . PHP_EOL);
                $pathCount += $paths->count();
            });

        return $pathCount;
    }
}
