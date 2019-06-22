<?php

namespace App\Providers;

use App\Services\iTunesService;
use Illuminate\Support\ServiceProvider;

class iTunesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('iTunes', static function (): iTunesService {
            return app(iTunesService::class);
        });
    }
}
