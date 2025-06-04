<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Contracts\MusicEncyclopedia;
use App\Services\LastfmService;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Services\LicenseService;
use App\Services\NullMusicEncyclopedia;
use App\Services\SpotifyService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SpotifyWebAPI\Session as SpotifySession;

class AppServiceProvider extends ServiceProvider
{
    public function boot(Builder $schema, DatabaseManager $db): void
    {
        // Fix utf8mb4-related error starting from Laravel 5.4
        $schema->defaultStringLength(191);

        Model::preventLazyLoading(!app()->isProduction());

        $this->enableOnDeleteCascadeForSqliteConnections($db);

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

        $this->app->when(LicenseService::class)
            ->needs('$hashSalt')
            ->give(config('app.key'));

        Route::bind('user', static fn (string $value) => User::query()->where('public_id', $value)->firstOrFail());
    }

    public function register(): void
    {
        if (class_exists('Laravel\Tinker\TinkerServiceProvider')) {
            $this->app->register('Laravel\Tinker\TinkerServiceProvider');
        }
    }

    public function enableOnDeleteCascadeForSqliteConnections(DatabaseManager $db): void
    {
        if ($db->connection() instanceof SQLiteConnection) {
            $db->statement($db->raw('PRAGMA foreign_keys = ON')->getValue($db->getQueryGrammar()));
        }
    }
}
