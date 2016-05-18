<?php

namespace App\Providers;

use App\Services\DropbeatMedia;
use Illuminate\Support\ServiceProvider;

class DropbeatMediaServiceProvider extends ServiceProvider
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
        app()->singleton('DropbeatMedia', function () {
            return new DropbeatMedia();
        });
    }
}
