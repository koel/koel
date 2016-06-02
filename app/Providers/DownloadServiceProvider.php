<?php

namespace App\Providers;

use App\Services\Download;
use Illuminate\Support\ServiceProvider;

class DownloadServiceProvider extends ServiceProvider
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
        app()->singleton('Download', function () {
            return new Download();
        });
    }
}
