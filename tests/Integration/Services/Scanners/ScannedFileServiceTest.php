<?php

namespace Tests\Integration\Services\Scanners;

use App\Models\ScannedFile;
use App\Services\Scanners\ScannedFileService;
use App\Values\Scanning\ScanResult;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScannedFileServiceTest extends TestCase
{
    private ScannedFileService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(ScannedFileService::class);
    }

    #[Test]
    public function startingAScanClearsScansAbandonedForADay(): void
    {
        $abandonedScanId = $this->recordOneFile();
        $this->travel(25)->hours();
        $runningScanId = $this->recordOneFile();
        $this->travel(2)->hours();

        $this->service->startScan();

        $this->assertDatabaseMissing(ScannedFile::class, ['scan_id' => $abandonedScanId]);
        $this->assertDatabaseHas(ScannedFile::class, ['scan_id' => $runningScanId]);
    }

    #[Test]
    public function recordingFlushesInBatches(): void
    {
        $recorder = $this->service->startScan();

        foreach (range(1, 501) as $i) {
            $recorder->record(ScanResult::success("/media/$i.mp3"));
        }

        $this->assertDatabaseCount(ScannedFile::class, 500);

        $summary = $recorder->finish();

        $this->assertDatabaseCount(ScannedFile::class, 501);
        self::assertSame(501, $summary->successCount);
    }

    private function recordOneFile(): string
    {
        $recorder = $this->service->startScan();
        $recorder->record(ScanResult::success('/media/foo.mp3'));

        return $recorder->finish()->scanId;
    }
}
