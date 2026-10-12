<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class QueuedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * False when Dispatcher ran the job inline, in which case the caller returns the result over
     * HTTP and a broadcast would deliver it a second time.
     */
    protected function wasQueued(): bool
    {
        return (bool) $this->job;
    }
}
