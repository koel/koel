<?php

namespace App\Providers;

use App\Services\MediaCache;
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
            return new MediaCache();
        });
    }
}
