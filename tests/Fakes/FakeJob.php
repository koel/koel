<?php

namespace Tests\Fakes;

use Illuminate\Contracts\Queue\ShouldQueue;

class FakeJob implements ShouldQueue
{
    public function handle(): string
    {
        return 'Job executed';
    }
}
