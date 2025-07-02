<?php

namespace Tests\Integration\Jobs;

use App\Jobs\RunCommandJob;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RunCommandJobTest extends TestCase
{
    #[Test]
    public function dispatch(): void
    {
        Artisan::expects('call')
            ->with('some:command');

        dispatch(new RunCommandJob('some:command'));
    }
}
