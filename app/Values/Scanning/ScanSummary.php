<?php

namespace App\Values\Scanning;

final readonly class ScanSummary
{
    private function __construct(
        public string $scanId,
        public int $successCount,
        public int $skippedCount,
        public int $errorCount,
        public int $pathCount,
    ) {}

    public static function make(
        string $scanId,
        int $successCount,
        int $skippedCount,
        int $errorCount,
        int $pathCount,
    ): self {
        return new self($scanId, $successCount, $skippedCount, $errorCount, $pathCount);
    }

    public function hasValidFiles(): bool
    {
        return ($this->successCount + $this->skippedCount) > 0;
    }

    public function isMissingResults(): bool
    {
        return ($this->successCount + $this->skippedCount + $this->errorCount) < $this->pathCount;
    }
}
