<?php

namespace App\Services\Scanners\Strategies;

use App\Services\Scanners\IndividualFileHandler;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResultCollection;

class SequentialScanStrategy
{
    public function __construct(
        private readonly IndividualFileHandler $fileHandler,
    ) {}

    /** @param array<string> $paths */
    public function scan(array $paths, ScanConfiguration $config, ?callable $onProgress = null): ScanResultCollection
    {
        $results = ScanResultCollection::create();

        foreach ($paths as $path) {
            $result = $this->fileHandler->handle($path, $config);
            $results->add($result);

            if ($onProgress) {
                $onProgress($result);
            }
        }

        return $results;
    }
}
