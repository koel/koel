<?php

namespace App\Services;

class Dispatcher
{
    /**
     * Whether the job should be queued or run synchronously.
     */
    private bool $shouldQueue;

    public function __construct()
    {
        $this->shouldQueue = config('queue.default') !== 'sync'
            && config('broadcasting.default') !== 'log'
            && config('broadcasting.default') !== 'null';
    }

    public function dispatch(object $job): mixed
    {
        // If the job should be queued, we simply dispatch it, assuming it already implements the `ShouldQueue`
        // interface.
        if ($this->shouldQueue) {
            return dispatch($job);
        }

        // Otherwise, we call the job's `handle` method directly, providing the necessary dependencies
        // and returning the result. This allows the caller (like a controller) to grab the result
        // and e.g., return it as a response.
        return app()->call([$job, 'handle']);
    }
}
