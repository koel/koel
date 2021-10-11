<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool used()
 */
class ITunes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'iTunes';
    }
}
