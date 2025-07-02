<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Artisan;

class RunCommandJob extends QueuedJob
{
    public function __construct(public readonly string $command)
    {
    }

    public function handle(): void
    {
        Artisan::call($this->command);
    }
}
