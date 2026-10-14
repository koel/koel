<?php

namespace App\Jobs;

use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Artisan;

class RunCommandJob extends QueuedJob
{
    private const int OVERLAP_LOCK_EXPIRY_SECONDS = 86_400;

    public int $timeout = 0;

    public function __construct(
        public readonly string $command,
    ) {}

    /** @return array<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->command))
                ->dontRelease()
                ->expireAfter(self::OVERLAP_LOCK_EXPIRY_SECONDS),
        ];
    }

    public function handle(): void
    {
        Artisan::call($this->command);
    }
}
