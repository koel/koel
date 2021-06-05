<?php

namespace App\Listeners;

use App\Services\MediaCacheService;

class ClearMediaCache
{
    private MediaCacheService $mediaCacheService;

    public function __construct(MediaCacheService $mediaCacheService)
    {
        $this->mediaCacheService = $mediaCacheService;
    }

    public function handle(): void
    {
        $this->mediaCacheService->clear();
    }
}
