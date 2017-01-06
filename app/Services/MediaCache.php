<?php

namespace App\Services;

use App\Models\Artist;
use Cache;

class MediaCache
{
    protected $keyName = 'media_cache';

    public function get()
    {
        $data = Cache::get($this->keyName);
        if (!$data) {
            $data = Artist::orderBy('name')->with('albums', with('albums.songs'))->get();
            Cache::forever($this->keyName, $data);
        }

        return $data;
    }

    public function clear()
    {
        Cache::forget($this->keyName);
    }
}
