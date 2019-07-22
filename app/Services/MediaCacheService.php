<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Cache\Repository as Cache;

class MediaCacheService
{
    private const CACHE_KEY = 'media_cache';

    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get media data.
     * If caching is enabled, the data will be retrieved from the cache.
     *
     * @return mixed[]
     */
    public function get(): array
    {
        if (!config('koel.cache_media')) {
            return $this->query();
        }

        return $this->cache->rememberForever(self::CACHE_KEY, function (): array {
            return $this->query();
        });
    }

    /**
     * Query fresh data from the database.
     *
     * @return mixed[]
     */
    private function query(): array
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
    public function clear(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
