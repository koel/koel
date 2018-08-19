<?php

namespace App\Providers;

use App\Services\iTunesService;
use Illuminate\Support\ServiceProvider;

class iTunesServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('iTunes', function () {
            return app()->make(iTunesService::class);
        });
    }
}
