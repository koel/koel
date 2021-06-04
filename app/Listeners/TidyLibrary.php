<?php

namespace App\Listeners;

use App\Services\MediaSyncService;

class TidyLibrary
{
    private MediaSyncService $mediaSyncService;

    public function __construct(MediaSyncService $mediaSyncService)
    {
        $this->mediaSyncService = $mediaSyncService;
    }

    public function handle(): void
    {
        $this->mediaSyncService->tidy();
    }
}
