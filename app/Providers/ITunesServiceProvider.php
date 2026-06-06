<?php

namespace App\Providers;

use App\Services\Integrations\ITunesService;
use Illuminate\Support\ServiceProvider;

class ITunesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('iTunes', static fn (): ITunesService => app(ITunesService::class));
    }
}
