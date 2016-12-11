<?php

namespace App\Providers;

use App\Services\iTunes;
use Illuminate\Support\ServiceProvider;

class iTunesServiceProvider extends ServiceProvider
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
        app()->singleton('iTunes', function () {
            return new iTunes();
        });
    }
}
