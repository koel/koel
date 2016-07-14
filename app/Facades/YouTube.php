<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class YouTube extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'YouTube';
    }
}
