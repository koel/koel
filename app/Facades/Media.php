<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Media extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Media';
    }
}
