<?php

namespace App\Services\Scanners;

use App\Enums\ScanResultType;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanSummary;

class ScanRecorder
{
    /** @var array<ScanResult> */
    private array $pendingResults = [];

    /** @var array<string, int> */
    private array $countsByType = [
        ScanResultType::SUCCESS->value => 0,
        ScanResultType::SKIPPED->value => 0,
        ScanResultType::ERROR->value => 0,
    ];

    public function __construct(
        private readonly ScannedFileService $scannedFileService,
        public readonly string $scanId,
    ) {}

    public function record(ScanResult $result): void
    {
        $this->pendingResults[] = $result;
        $this->countsByType[$result->type->value]++;

        if (count($this->pendingResults) >= 500) {
            $this->flush();
        }
    }

    public function finish(): ScanSummary
    {
        $this->flush();

        return ScanSummary::make(
            scanId: $this->scanId,
            successCount: $this->countsByType[ScanResultType::SUCCESS->value],
            skippedCount: $this->countsByType[ScanResultType::SKIPPED->value],
            errorCount: $this->countsByType[ScanResultType::ERROR->value],
        );
    }

    private function flush(): void
    {
        if (!$this->pendingResults) {
            return;
        }

        $this->scannedFileService->recordScannedFiles($this->scanId, $this->pendingResults);
        $this->pendingResults = [];
    }
}
