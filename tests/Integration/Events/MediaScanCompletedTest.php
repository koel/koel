<?php

namespace Tests\Integration\Events;

use App\Events\MediaScanCompleted;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaScanCompletedTest extends TestCase
{
    #[Test]
    public function dispatchingPushesNothingOntoTheQueue(): void
    {
        Queue::fake();

        event(new MediaScanCompleted(ScanResultCollection::create()->add(ScanResult::success('/media/foo.mp3'))));

        Queue::assertNothingPushed();
    }
}
