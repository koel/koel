<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Upload extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Upload';
    }
}
