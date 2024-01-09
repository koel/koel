<?php

namespace App\Facades;

use App\Services\License\FakePlusLicenseService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isPlus()
 * @method static bool isCommunity()
 * @see \App\Services\License\LicenseService
 */
class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'License';
    }

    public static function fakePlusLicense(): void
    {
        self::swap(app(FakePlusLicenseService::class));
    }
}
