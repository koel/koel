<?php

namespace App\Providers;

use App\Services\ITunesService;
use Illuminate\Support\ServiceProvider;

class ITunesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('iTunes', static function (): ITunesService {
            return app(ITunesService::class);
        });
    }
}
