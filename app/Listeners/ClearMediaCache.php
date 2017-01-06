<?php

namespace App\Listeners;

use MediaCache;

class ClearMediaCache
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Fired every time a LibraryChanged event is triggered.
     * Clears the media cache.
     */
    public function handle()
    {
        MediaCache::clear();
    }
}
