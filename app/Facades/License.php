<?php

namespace App\Facades;

use App\Exceptions\KoelPlusRequiredException;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isPlus()
 * @method static bool isCommunity()
 * @see \App\Services\LicenseService
 */
class License extends Facade
{
    public static function requirePlus(): void
    {
        throw_unless(static::isPlus(), KoelPlusRequiredException::class);
    }

    protected static function getFacadeAccessor(): string
    {
        return 'License';
    }
}
