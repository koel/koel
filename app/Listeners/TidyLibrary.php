<?php

namespace App\Listeners;

use App\Services\MediaSyncService;
use Exception;

class TidyLibrary
{
    private $mediaSyncService;

    public function __construct(MediaSyncService $mediaSyncService)
    {
        $this->mediaSyncService = $mediaSyncService;
    }

    /**
     * Fired every time a LibraryChanged event is triggered.
     * Tidies up our lib.
     *
     * @throws Exception
     */
    public function handle()
    {
        $this->mediaSyncService->tidy();
    }
}
