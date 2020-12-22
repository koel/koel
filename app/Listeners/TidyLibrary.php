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
     * @throws Exception
     */
    public function handle(): void
    {
        $this->mediaSyncService->tidy();
    }
}
