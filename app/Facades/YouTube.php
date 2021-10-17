<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled()
 */
class YouTube extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'YouTube';
    }
}
