<?php

namespace App\Services\Scanners;

use App\Models\ScannedFile;
use App\Repositories\ScannedFileRepository;
use App\Values\Scanning\ScanResult;
use Illuminate\Support\Str;

class ScannedFileService
{
    public function __construct(
        private readonly ScannedFileRepository $scannedFileRepository,
    ) {}

    public function startScan(): ScanRecorder
    {
        $abandonedScanIds = $this->scannedFileRepository->getIdsOfScansInactiveSince(now()->subDay());

        if ($abandonedScanIds) {
            ScannedFile::query()->whereIn('scan_id', $abandonedScanIds)->delete();
        }

        return new ScanRecorder($this, Str::uuid()->toString());
    }

    /** @param array<ScanResult> $results */
    public function recordScannedFiles(string $scanId, array $results): void
    {
        $now = now();

        ScannedFile::query()->insert(array_map(static fn (ScanResult $result): array => [
            'scan_id' => $scanId,
            'path' => $result->path,
            'path_hash' => simple_hash($result->path),
            'type' => $result->type->value,
            'error' => $result->error,
            'created_at' => $now,
        ], $results));
    }

    public function deleteScannedFiles(string $scanId): void
    {
        ScannedFile::query()->where('scan_id', $scanId)->delete();
    }
}
