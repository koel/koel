<?php

namespace App\Console;

use App\Console\Commands\PruneLibraryCommand;
use App\Console\Commands\ScanCommand;
use App\Console\Commands\SyncPodcastsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(ScanCommand::class)->daily();
        $schedule->command(PruneLibraryCommand::class)->daily();
        $schedule->command(SyncPodcastsCommand::class)->daily();
    }
}
