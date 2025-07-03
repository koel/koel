<?php

namespace App\Services\Scanners;

use App\Enums\ScanEvent;
use App\Events\MediaScanCompleted;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResultCollection;

class DirectoryScanner extends Scanner
{
    /** @var array<string, callable> */
    private array $events = [];

    public function scan(string $directory, ScanConfiguration $config): ScanResultCollection
    {
        self::setSystemRequirements();

        $files = $this->gatherFiles($directory);

        if (isset($this->events[ScanEvent::PATHS_GATHERED->name])) {
            $this->events[ScanEvent::PATHS_GATHERED->name]($files);
        }

        $results = ScanResultCollection::create();

        foreach ($files as $file) {
            $result = $this->handleIndividualFile($file->getRealPath(), $config);
            $results->add($result);

            if (isset($this->events[ScanEvent::SCAN_PROGRESS->name])) {
                $this->events[ScanEvent::SCAN_PROGRESS->name]($result);
            }
        }

        event(new MediaScanCompleted($results));

        return $results;
    }

    public function on(ScanEvent $event, callable $callback): void
    {
        $this->events[$event->name] = $callback;
    }
}
