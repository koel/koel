<?php

namespace App\Providers;

use App\Services\License\LicenseServiceInterface;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('License', static fn (): LicenseServiceInterface => app(LicenseServiceInterface::class));
    }
}
