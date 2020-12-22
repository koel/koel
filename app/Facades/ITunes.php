<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class iTunes extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'iTunes';
    }
}
