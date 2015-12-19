<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Lastfm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Lastfm';
    }
}
