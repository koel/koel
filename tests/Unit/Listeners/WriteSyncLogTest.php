<?php

namespace Tests\Unit\Listeners;

use App\Events\MediaSyncCompleted;
use App\Listeners\WriteSyncLog;
use App\Values\SyncResult;
use App\Values\SyncResultCollection;
use Carbon\Carbon;
use Tests\TestCase;

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
        @unlink(storage_path('logs/sync-20210102-123456.log'));
        config(['koel.sync_log_level' => $this->originalLogLevel]);

        parent::tearDown();
    }

    public function testHandleWithLogLevelAll(): void
    {
        config(['koel.sync_log_level' => 'all']);

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertStringEqualsFile(
            storage_path('logs/sync-20210102-123456.log'),
            file_get_contents(__DIR__ . '/../../blobs/sync-log-all.log')
        );
    }

    public function testHandleWithLogLevelError(): void
    {
        config(['koel.sync_log_level' => 'error']);

        $this->listener->handle(self::createSyncCompleteEvent());

        self::assertStringEqualsFile(
            storage_path('logs/sync-20210102-123456.log'),
            file_get_contents(__DIR__ . '/../../blobs/sync-log-error.log')
        );
    }

    private static function createSyncCompleteEvent(): MediaSyncCompleted
    {
        $resultCollection = SyncResultCollection::create()
            ->add(SyncResult::success('/media/foo.mp3'))
            ->add(SyncResult::error('/media/baz.mp3', 'Something went wrong'))
            ->add(SyncResult::error('/media/qux.mp3', 'Something went horribly wrong'))
            ->add(SyncResult::skipped('/media/bar.mp3'));

        return new MediaSyncCompleted($resultCollection);
    }
}
