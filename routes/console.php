<?php

use App\Console\Commands\CleanUpTempFilesCommand;
use App\Console\Commands\PruneLibraryCommand;
use App\Console\Commands\ScanCommand;
use App\Console\Commands\SyncPodcastsCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ScanCommand::class)->daily();
Schedule::command(PruneLibraryCommand::class)->daily();
Schedule::command(SyncPodcastsCommand::class)->daily();
Schedule::command(CleanUpTempFilesCommand::class)->daily();
