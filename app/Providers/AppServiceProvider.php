<?php

namespace App\Providers;

use App\Enums\Acl\Role;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use App\Rules\ValidRadioStationUrl;
use App\Services\Contracts\Encyclopedia;
use App\Services\Geolocation\Contracts\GeolocationService;
use App\Services\Geolocation\IPinfoService;
use App\Services\LastfmService;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Services\LicenseService;
use App\Services\MusicBrainzService;
use App\Services\NullEncyclopedia;
use App\Services\Scanners\Contracts\ScannerCacheStrategy as ScannerCacheStrategyContract;
use App\Services\Scanners\ScannerCacheStrategy;
use App\Services\Scanners\ScannerNoCacheStrategy;
use App\Services\SpotifyService;
use App\Services\TicketmasterService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
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

        self::enableOnDeleteCascadeForSqliteConnections($db);

        // disable wrapping JSON resource in a `data` key
        JsonResource::withoutWrapping();

        self::grantAllPermissionsToSuperAdminRole();

        $this->app->bind(SpotifySession::class, static function () {
            return SpotifyService::enabled()
                ? new SpotifySession(
                    config('koel.services.spotify.client_id'),
                    config('koel.services.spotify.client_secret'),
                )
                : null;
        });

        $this->app->bind(Encyclopedia::class, static function () {
            // Prefer Last.fm over MusicBrainz, and fall back to a null encyclopedia.
            if (LastfmService::enabled()) {
                return app(LastfmService::class);
            }

            if (MusicBrainzService::enabled()) {
                return app(MusicBrainzService::class);
            }

            return app(NullEncyclopedia::class);
        });

        $this->app->bind(LicenseServiceInterface::class, LicenseService::class);

        $this->app->when(LicenseService::class)
            ->needs('$hashSalt')
            ->give(config('app.key'));

        $this->app->bind(ScannerCacheStrategyContract::class, static function () {
            // Use a no-cache strategy for unit tests to ensure consistent results
            return app()->runningUnitTests() ? app(ScannerNoCacheStrategy::class) : app(ScannerCacheStrategy::class);
        });

        $this->app->singleton(ValidRadioStationUrl::class, static fn () => new ValidRadioStationUrl());

        Route::bind('genre', static function (string $value): ?Genre {
            if ($value === Genre::NO_GENRE_PUBLIC_ID) {
                return null;
            }

            return Genre::query()->where('public_id', $value)->firstOrFail();
        });

        Relation::morphMap([
            'playable' => Song::class,
            'album' => Album::class,
            'artist' => Artist::class,
            'podcast' => Podcast::class,
            'radio-station' => RadioStation::class,
            'playlist' => Playlist::class,
        ]);

        $this->app->when(TicketmasterService::class)
            ->needs('$defaultCountryCode')
            ->give(config('koel.services.ticketmaster.default_country_code'));

        $this->app->bind(GeolocationService::class, static function (): GeolocationService {
            return app(IPinfoService::class);
        });
    }

    public function register(): void
    {
        if (class_exists('Laravel\Tinker\TinkerServiceProvider')) {
            $this->app->register('Laravel\Tinker\TinkerServiceProvider');
        }
    }

    private static function enableOnDeleteCascadeForSqliteConnections(DatabaseManager $db): void
    {
        if ($db->connection() instanceof SQLiteConnection) {
            $db->statement($db->raw('PRAGMA foreign_keys = ON')->getValue($db->getQueryGrammar()));
        }
    }

    private static function grantAllPermissionsToSuperAdminRole(): void
    {
        Gate::after(static fn (User $user) => $user->hasRole(Role::ADMIN));
    }
}
