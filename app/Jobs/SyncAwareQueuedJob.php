<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class SyncAwareQueuedJob implements ShouldQueue
{
    use Dispatchable {
        Dispatchable::dispatch as baseDispatch;
    }

    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Dispatch the job if a proper queue and broadcasting driver is configured.
     * If not, run the job synchronously and return the result.
     */
    public static function dispatch(...$arguments): mixed
    {
        if (self::usesQueue()) {
            return static::baseDispatch(...$arguments);
        }

        return app()->call([new static(...$arguments), 'handle']);
    }

    public static function usesQueue(): bool
    {
        return config('queue.default') !== 'sync' && config('broadcasting.default') !== 'log';
    }
}
