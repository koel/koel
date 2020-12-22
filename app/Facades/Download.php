<?php

namespace App\Facades;

use App\Models\Song;
use Illuminate\Support\Facades\Facade;

/**
 * @method static fromSong(Song $song)
 */
class Download extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Download';
    }
}
