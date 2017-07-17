<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Musixmatch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Musixmatch';
    }
}