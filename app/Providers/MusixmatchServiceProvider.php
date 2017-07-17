<?php

namespace App\Providers;

use App\Services\Musixmatch;
use Illuminate\Support\ServiceProvider;

class MusixmatchServiceProvider extends ServiceProvider
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
        app()->singleton('Musixmatch', function () {
            return new Musixmatch();
        });
    }
}