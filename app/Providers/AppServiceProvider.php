<?php

namespace App\Providers;

use App\Services\SpotifyService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory as Validator;
use SpotifyWebAPI\Session as SpotifySession;

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

        // disable wrapping JSON resource in a `data` key
        JsonResource::withoutWrapping();

        $this->app->bind(SpotifySession::class, static function () {
            return SpotifyService::enabled()
                ? new SpotifySession(config('koel.spotify.client_id'), config('koel.spotify.client_secret'))
                : null;
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment() !== 'production' && class_exists('Laravel\Tinker\TinkerServiceProvider')) {
            $this->app->register('Laravel\Tinker\TinkerServiceProvider');
        }
    }
}
