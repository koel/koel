<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string detectUTFEncoding(string $name)
 */
class Util extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Util';
    }
}
