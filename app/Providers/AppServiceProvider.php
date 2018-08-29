<?php

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use DB;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Tinker\TinkerServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Fix utf8mb4-related error starting from Laravel 5.4
        Schema::defaultStringLength(191);

        // Enable on delete cascade for sqlite connections
        if (DB::connection() instanceof SQLiteConnection) {
            DB::statement(DB::raw('PRAGMA foreign_keys = ON'));
        }

        // Add some custom validation rules
        Validator::extend('path.valid', function ($attribute, $value, $parameters, $validator) {
            return is_dir($value) && is_readable($value);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->app->environment('production')) {
            $this->app->register(TinkerServiceProvider::class);
            $this->app->register(IdeHelperServiceProvider::class);
        }
    }
}
