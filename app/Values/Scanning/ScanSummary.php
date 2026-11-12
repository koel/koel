<?php

namespace App\Values\Scanning;

final readonly class ScanSummary
{
    private function __construct(
        public string $scanId,
        public int $successCount,
        public int $skippedCount,
        public int $errorCount,
    ) {}

    public static function make(string $scanId, int $successCount, int $skippedCount, int $errorCount): self
    {
        return new self($scanId, $successCount, $skippedCount, $errorCount);
    }

    public function hasValidFiles(): bool
    {
        return ($this->successCount + $this->skippedCount) > 0;
    }
}
