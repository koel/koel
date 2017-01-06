<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class MediaCache extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'MediaCache';
    }
}
