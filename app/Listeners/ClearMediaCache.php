<?php

namespace App\Listeners;

use App\Services\MediaCacheService;

class ClearMediaCache
{
    private $mediaCacheService;

    public function __construct(MediaCacheService $mediaCacheService)
    {
        $this->mediaCacheService = $mediaCacheService;
    }

    /**
     * Fired every time a LibraryChanged event is triggered.
     * Clears the media cache.
     */
    public function handle()
    {
        $this->mediaCacheService->clear();
    }
}
