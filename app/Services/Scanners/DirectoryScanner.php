<?php

namespace App\Services\Scanners;

use App\Enums\ScanEvent;
use App\Events\MediaScanCompleted;
use App\Services\Scanners\Strategies\ParallelScanStrategy;
use App\Services\Scanners\Strategies\SequentialScanStrategy;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
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
    ) {
        parent::__construct($fileHandler, $finder);
    }

    public function scan(string $directory, ScanConfiguration $config, int $jobs = 1): ScanResultCollection
    {
        self::setSystemRequirements();

        $files = $this->gatherFiles($directory);

        if (isset($this->events[ScanEvent::PATHS_GATHERED->name])) {
            $this->events[ScanEvent::PATHS_GATHERED->name]($files);
        }

        $onProgress = isset($this->events[ScanEvent::SCAN_PROGRESS->name])
            ? fn (ScanResult $result) => $this->events[ScanEvent::SCAN_PROGRESS->name]($result)
            : null;

        $results = $jobs > 1
            ? $this->parallelStrategy->scan($files, $config, $jobs, $onProgress)
            : $this->sequentialStrategy->scan($files, $config, $onProgress);

        event(new MediaScanCompleted($results));

        return $results;
    }

    public function on(ScanEvent $event, callable $callback): void
    {
        $this->events[$event->name] = $callback;
    }
}
