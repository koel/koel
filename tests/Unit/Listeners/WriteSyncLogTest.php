<?php

namespace Tests\Unit\Listeners;

use App\Events\MediaScanCompleted;
use App\Listeners\WriteScanLog;
use App\Services\Scanners\ScannedFileService;
use App\Values\Scanning\ScanResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class WriteSyncLogTest extends TestCase
{
    private WriteScanLog $listener;
    private string $originalLogLevel;
    private string $originalStoragePath;
    private string $storagePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = app(WriteScanLog::class);
        $this->originalLogLevel = config('koel.sync_log_level');
        Carbon::setTestNow(Carbon::create(2021, 1, 2, 12, 34, 56));

        $this->originalStoragePath = $this->app->storagePath();
        $this->storagePath = sys_get_temp_dir() . '/koel-write-scan-log-test-' . uniqid();
        File::makeDirectory($this->storagePath . '/logs', 0o755, true);
        $this->app->useStoragePath($this->storagePath);
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->originalStoragePath);
        File::deleteDirectory($this->storagePath);
        config(['koel.sync_log_level' => $this->originalLogLevel]);

        parent::tearDown();
    }

    #[Test]
    public function handleWritesNothingWhenThereIsNothingToReport(): void
    {
        config(['koel.sync_log_level' => 'error']);

        $this->listener->handle(self::recordScan(
            ScanResult::success('/media/foo.mp3'),
            ScanResult::skipped('/media/bar.mp3'),
        ));

        self::assertFileDoesNotExist(storage_path('logs/sync-20210102-123456.log'));
    }

    #[Test]
    public function handlePrunesOlderLogsBeyondTheRetention(): void
    {
        config(['koel.sync_log_level' => 'error', 'koel.scan_log_max_files' => 2]);

        foreach (['20200101-000000', '20200102-000000', '20200103-000000'] as $stamp) {
            File::put(storage_path("logs/sync-$stamp.log"), 'old');
        }

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertFileExists(storage_path('logs/sync-20210102-123456.log'));
        self::assertFileExists(storage_path('logs/sync-20200103-000000.log'));
        self::assertFileDoesNotExist(storage_path('logs/sync-20200102-000000.log'));
        self::assertFileDoesNotExist(storage_path('logs/sync-20200101-000000.log'));
    }

    /** @return array<string, array{0: int|string}> */
    public static function provideInvalidRetentionValues(): array
    {
        return [
            'empty string' => [''],
            'not a number' => ['abc'],
            'negative' => [-1],
        ];
    }

    #[DataProvider('provideInvalidRetentionValues')]
    #[Test]
    public function handleFallsBackToTheDefaultRetentionWhenTheSettingIsInvalid(int|string $value): void
    {
        config(['koel.sync_log_level' => 'error', 'koel.scan_log_max_files' => $value]);

        foreach (range(1, 31) as $day) {
            File::put(storage_path(sprintf('logs/sync-202001%02d-000000.log', $day)), 'old');
        }

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertCount(30, File::glob(storage_path('logs/sync-*.log')));
        self::assertFileDoesNotExist(storage_path('logs/sync-20200101-000000.log'));
    }

    #[Test]
    public function handleKeepsEveryLogWhenRetentionIsZero(): void
    {
        config(['koel.sync_log_level' => 'error', 'koel.scan_log_max_files' => 0]);
        File::put(storage_path('logs/sync-20200101-000000.log'), 'old');

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertFileExists(storage_path('logs/sync-20200101-000000.log'));
    }

    #[Test]
    public function handleWithLogLevelAll(): void
    {
        config(['koel.sync_log_level' => 'all']);

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertStringEqualsFile(
            storage_path('logs/sync-20210102-123456.log'),
            File::get(test_path('fixtures/sync-log-all.log')),
        );
    }

    #[Test]
    public function handleWithLogLevelError(): void
    {
        config(['koel.sync_log_level' => 'error']);

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertStringEqualsFile(
            storage_path('logs/sync-20210102-123456.log'),
            File::get(test_path('fixtures/sync-log-error.log')),
        );
    }

    private static function createSyncCompleteEvent(): MediaScanCompleted
    {
        return self::recordScan(
            ScanResult::success('/media/foo.mp3'),
            ScanResult::error('/media/baz.mp3', 'Something went wrong'),
            ScanResult::error('/media/qux.mp3', 'Something went horribly wrong'),
            ScanResult::skipped('/media/bar.mp3'),
        );
    }

    private static function recordScan(ScanResult ...$results): MediaScanCompleted
    {
        $recorder = app(ScannedFileService::class)->startScan();

        foreach ($results as $result) {
            $recorder->record($result);
        }

        return new MediaScanCompleted($recorder->finish());
    }
}
