<?php

namespace App\Providers;

use App\Services\ApiClients\ApiClient;
use App\Services\ApiClients\LemonSqueezyApiClient;
use App\Services\LastfmService;
use App\Services\License\LicenseServiceInterface;
use App\Services\LicenseService;
use App\Services\MusicEncyclopedia;
use App\Services\NullMusicEncyclopedia;
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

        $this->app->bind(MusicEncyclopedia::class, function () {
            return $this->app->get(LastfmService::enabled() ? LastfmService::class : NullMusicEncyclopedia::class);
        });

        $this->app->bind(LicenseServiceInterface::class, LicenseService::class);
        $this->app->bind(ApiClient::class, LemonSqueezyApiClient::class);

        $this->app->when(LicenseService::class)
            ->needs('$hashSalt')
            ->give(config('app.key'));
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
