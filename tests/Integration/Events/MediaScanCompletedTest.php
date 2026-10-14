<?php

namespace Tests\Integration\Events;

use App\Events\MediaScanCompleted;
use App\Values\Scanning\ScanSummary;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaScanCompletedTest extends TestCase
{
    #[Test]
    public function dispatchingPushesNothingOntoTheQueue(): void
    {
        Queue::fake();

        event(new MediaScanCompleted(ScanSummary::make('scan-id', 1, 0, 0)));

        Queue::assertNothingPushed();
    }
}
