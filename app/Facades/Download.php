<?php

namespace App\Facades;

use App\Models\Song;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getLocalPath(Song $song)
 * @see \App\Services\DownloadService
 */
class Download extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Download';
    }
}
