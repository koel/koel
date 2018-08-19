<?php

namespace App\Providers;

use App\Services\LastfmService;
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
            return app()->make(LastfmService::class);
        });
    }
}
