<?php

namespace App\Providers;

use App\Services\Lastfm;
use Illuminate\Support\ServiceProvider;

class LastfmServiceProvider extends ServiceProvider
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
        app()->singleton('Lastfm', function () {
            return new Lastfm();
        });
    }
}
