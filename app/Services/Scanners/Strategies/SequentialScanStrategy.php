<?php

namespace App\Services\Scanners\Strategies;

use App\Services\Scanners\IndividualFileHandler;
use App\Values\Scanning\ScanConfiguration;

class SequentialScanStrategy
{
    public function __construct(
        private readonly IndividualFileHandler $fileHandler,
    ) {}

    /** @param iterable<string> $paths */
    public function scan(iterable $paths, ScanConfiguration $config, callable $onResult): void
    {
        foreach ($paths as $path) {
            $onResult($this->fileHandler->handle($path, $config));
        }
    }
}
