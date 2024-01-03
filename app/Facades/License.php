<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isPlus()
 * @method static bool isCommunity()
 */
class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'License';
    }
}
