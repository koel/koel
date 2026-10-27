<?php

use App\Facades\ITunes;
use App\Http\Controllers\AppManifestController;
use App\Http\Controllers\AuthorizeDropboxController;
use App\Http\Controllers\Demo\IndexController as DemoIndexController;
use App\Http\Controllers\Demo\NewSessionController;
use App\Http\Controllers\DownloadSongController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\LastfmController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\RemoteManifestController;
use App\Http\Controllers\SSO\GoogleCallbackController;
use App\Http\Controllers\SSO\OpenIDConnectCallbackController;
use App\Http\Controllers\StreamEmbedController;
use App\Http\Controllers\StreamRadioController;
use App\Http\Controllers\ViewSongOnITunesController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Facades\Socialite;

$sessionMiddleware = [StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class];

Route::middleware('web')->group(static function () use ($sessionMiddleware): void {
    // Using a closure to determine the controller instead of static configuration to allow for testing.
    $showApp = static fn () => app()->call(
        config('koel.misc.demo') ? DemoIndexController::class : IndexController::class,
    );

    Route::get('/', $showApp);

    Route::fallback(static function () use ($showApp) {
        abort_unless(config('koel.clean_urls.enabled') && !request()->is('api/*'), Response::HTTP_NOT_FOUND);

        return $showApp();
    });

    Route::get('remote', static fn () => view('remote'));

    Route::get('manifest.json', AppManifestController::class)->name('manifest');
    Route::get('manifest-remote.json', RemoteManifestController::class)->name('manifest.remote');

    Route::get('lastfm/callback', [LastfmController::class, 'callback'])->name('lastfm.callback');

    Route::middleware('auth')->group(static function (): void {
        if (ITunes::used()) {
            Route::get('itunes/song/{album}', ViewSongOnITunesController::class)->name('iTunes.viewSong');
        }
    });

    Route::get('auth/google/redirect', static fn () => Socialite::driver('google')->redirect());
    Route::get('auth/google/callback', GoogleCallbackController::class);

    Route::get('auth/oidc/redirect', static fn () => Socialite::driver('oidc')->redirect());
    Route::get('auth/oidc/callback', OpenIDConnectCallbackController::class);

    Route::get('dropbox/authorize/{key}', AuthorizeDropboxController::class)->name('dropbox.authorize');

    Route::middleware('audio.auth')
        ->withoutMiddleware($sessionMiddleware)
        ->group(static function (): void {
            Route::get('play/{songId}/{transcode?}/{bitRate?}', PlayController::class)->name('song.play');

            Route::get('radio/stream/{radioStation}', StreamRadioController::class)->name('radio.stream')->middleware(
                'radio.enabled',
            );

            if (config('koel.download.allow')) {
                Route::prefix('download')->group(static function (): void {
                    Route::get('songs', DownloadSongController::class)->name('songs.download');
                });
            }
        });

    Route::get('embeds/{embed}/stream/{song}/{options}', StreamEmbedController::class)
        ->name('embeds.stream')
        ->middleware('embeds.enabled', 'signed', 'throttle:60,1,embeds')
        ->withoutMiddleware($sessionMiddleware);
});

Route::middleware('web')
    ->prefix('demo')
    ->group(static function (): void {
        Route::get('/new-session', NewSessionController::class)->name('demo.new-session')->middleware('throttle:10,1');
    });
