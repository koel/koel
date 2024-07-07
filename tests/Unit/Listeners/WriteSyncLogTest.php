<?php

namespace Tests\Unit\Listeners;

use App\Events\MediaScanCompleted;
use App\Listeners\WriteSyncLog;
use App\Values\ScanResult;
use App\Values\ScanResultCollection;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

use function Tests\test_path;

class WriteSyncLogTest extends TestCase
{
    private WriteSyncLog $listener;
    private string $originalLogLevel;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = new WriteSyncLog();
        $this->originalLogLevel = config('koel.sync_log_level');
        Carbon::setTestNow(Carbon::create(2021, 1, 2, 12, 34, 56));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('logs/sync-20210102-123456.log'));
        config(['koel.sync_log_level' => $this->originalLogLevel]);

        parent::tearDown();
    }

    public function testHandleWithLogLevelAll(): void
    {
        config(['koel.sync_log_level' => 'all']);

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertStringEqualsFile(
            storage_path('logs/sync-20210102-123456.log'),
            File::get(test_path('blobs/sync-log-all.log'))
        );
    }

    public function testHandleWithLogLevelError(): void
    {
        config(['koel.sync_log_level' => 'error']);

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertStringEqualsFile(
            storage_path('logs/sync-20210102-123456.log'),
            File::get(test_path('blobs/sync-log-error.log'))
        );
    }

    private static function createSyncCompleteEvent(): MediaScanCompleted
    {
        $resultCollection = ScanResultCollection::create()
            ->add(ScanResult::success('/media/foo.mp3'))
            ->add(ScanResult::error('/media/baz.mp3', 'Something went wrong'))
            ->add(ScanResult::error('/media/qux.mp3', 'Something went horribly wrong'))
            ->add(ScanResult::skipped('/media/bar.mp3'));

        return new MediaScanCompleted($resultCollection);
    }
}
