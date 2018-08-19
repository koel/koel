<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Cache\Repository as Cache;

class MediaCacheService
{
    private $cache;
    private $keyName = 'media_cache';

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get media data.
     * If caching is enabled, the data will be retrieved from the cache.
     *
     * @return array
     */
    public function get()
    {
        if (!config('koel.cache_media')) {
            return $this->query();
        }

        return $this->cache->rememberForever($this->keyName, function () {
            return $this->query();
        });
    }

    /**
     * Query fresh data from the database.
     *
     * @return array
     */
    private function query()
    {
        return [
            'albums' => Album::orderBy('name')->get(),
            'artists' => Artist::orderBy('name')->get(),
            'songs' => Song::all(),
        ];
    }

    /**
     * Clear the media cache.
     */
    public function clear()
    {
        $this->cache->forget($this->keyName);
    }
}
