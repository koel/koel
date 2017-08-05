<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Cache;

class MediaCache
{
    protected $keyName = 'media_cache';

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

        return Cache::rememberForever($this->keyName, function () {
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
        Cache::forget($this->keyName);
    }
}
