<?php

namespace App\Console\Commands;

use App\Events\LibraryChanged;
use App\Services\MediaSyncService;
use Illuminate\Console\Command;

class TidyLibraryCommand extends Command
{
    protected $signature = 'koel:tidy';
    protected $description = 'Tidy up the library by deleting empty artists and albums';

    private $mediaSyncService;

    public function __construct(MediaSyncService $mediaSyncService)
    {
        parent::__construct();

        $this->mediaSyncService = $mediaSyncService;
    }

    public function handle(): void
    {
        $this->mediaSyncService->tidy();
        event(new LibraryChanged());
        $this->info('Empty artists and albums removed.');
    }
}
