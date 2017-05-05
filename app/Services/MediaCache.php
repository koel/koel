<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Cache;

class MediaCache
{
    protected $keyName = 'media_cache';

    public function get()
    {
        if (!config('koel.cache_media')) {
            return $this->query();
        }

        $data = Cache::get($this->keyName);
        if (!$data) {
            $data = $this->query();
            Cache::forever($this->keyName, $data);
        }

        return $data;
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

    public function clear()
    {
        Cache::forget($this->keyName);
    }
}
