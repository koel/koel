<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Download extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Download';
    }
}
