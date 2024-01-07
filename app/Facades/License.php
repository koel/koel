<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isPlus()
 * @method static bool isCommunity()
 * @see \App\Services\LicenseService
 */
class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'License';
    }
}
