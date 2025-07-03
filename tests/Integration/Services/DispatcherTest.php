<?php

namespace Tests\Integration\Services;

use App\Services\Dispatcher;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeJob;
use Tests\TestCase;

class DispatcherTest extends TestCase
{
    private string $queueConfig;
    private string $broadcastingConfig;

    public function setUp(): void
    {
        parent::setUp();

        $this->queueConfig = config('queue.default');
        $this->broadcastingConfig = config('broadcasting.default');
    }

    #[Test]
    public function dispatchIfQueueSupported(): void
    {
        Bus::fake();
        config(['queue.default' => 'redis']);
        config(['broadcasting.default' => 'pusher']);

        $dispatcher = app(Dispatcher::class);
        $dispatcher->dispatch(new FakeJob());

        Bus::assertDispatched(FakeJob::class);
    }

    #[Test]
    public function executeJobSynchronouslyIfQueueNotSupported(): void
    {
        Bus::fake();

        $dispatcher = app(Dispatcher::class);
        $result = $dispatcher->dispatch(new FakeJob());

        self::assertSame('Job executed', $result);

        Bus::assertNotDispatched(FakeJob::class);
    }

    protected function tearDown(): void
    {
        config(['queue.default' => $this->queueConfig]);
        config(['broadcasting.default' => $this->broadcastingConfig]);

        parent::tearDown();
    }
}
