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

    /** @param iterable<\SplFileInfo> $files */
    public function scan(iterable $files, ScanConfiguration $config, ?callable $onProgress = null): ScanResultCollection
    {
        $results = ScanResultCollection::create();

        foreach ($files as $file) {
            $result = $this->fileHandler->handle($file->getRealPath(), $config);
            $results->add($result);

            if ($onProgress) {
                $onProgress($result);
            }
        }

        return $results;
    }
}
