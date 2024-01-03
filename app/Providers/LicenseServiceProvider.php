<?php

namespace App\Providers;

use App\Services\LicenseService;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('License', static fn (): LicenseService => app(LicenseService::class));
    }
}
