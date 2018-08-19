<?php

namespace App\Providers;

use App\Services\MediaCacheService;
use Illuminate\Support\ServiceProvider;

class MediaCacheServiceProvider extends ServiceProvider
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
        app()->singleton('MediaCache', function () {
            return new MediaCacheService();
        });
    }
}
