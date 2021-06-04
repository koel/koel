<?php

namespace App\Providers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory as Validator;
use Laravel\Tinker\TinkerServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(Builder $schema, DatabaseManager $db, Validator $validator): void
    {
        // Fix utf8mb4-related error starting from Laravel 5.4
        $schema->defaultStringLength(191);

        // Enable on delete cascade for sqlite connections
        if ($db->connection() instanceof SQLiteConnection) {
            $db->statement($db->raw('PRAGMA foreign_keys = ON'));
        }

        // Add some custom validation rules
        $validator->extend('path.valid', static fn ($attribute, $value): bool => is_dir($value) && is_readable($value));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(TinkerServiceProvider::class);
        }
    }
}
