<?php

namespace Tests\Fakes;

use Illuminate\Contracts\Queue\ShouldQueue;
use RuntimeException;
use Throwable;

class FailingFakeJob implements ShouldQueue
{
    public ?Throwable $failure = null;

    public function handle(): void
    {
        throw new RuntimeException('Job failed');
    }

    public function failed(Throwable $exception): void
    {
        $this->failure = $exception;
    }
}
