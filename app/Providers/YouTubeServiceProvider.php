<?php

namespace App\Providers;

use App\Services\YouTubeService;
use Illuminate\Support\ServiceProvider;

class YouTubeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('YouTube', function () {
            return app()->make(YouTubeService::class);
        });
    }
}
