<?php

use App\Facades\ITunes;
use App\Http\Controllers\AuthorizeDropboxController;
use App\Http\Controllers\Demo\IndexController as DemoIndexController;
use App\Http\Controllers\Demo\NewSessionController;
use App\Http\Controllers\Download\DownloadAlbumController;
use App\Http\Controllers\Download\DownloadArtistController;
use App\Http\Controllers\Download\DownloadFavoritesController;
use App\Http\Controllers\Download\DownloadPlaylistController;
use App\Http\Controllers\Download\DownloadSongsController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\LastfmController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\SSO\GoogleCallbackController;
use App\Http\Controllers\StreamEmbedController;
use App\Http\Controllers\StreamRadioController;
use App\Http\Controllers\ViewSongOnITunesController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::middleware('web')->group(static function (): void {
    // Using a closure to determine the controller instead of static configuration to allow for testing.
    Route::get(
        '/',
        static fn () => app()->call(config('koel.misc.demo') ? DemoIndexController::class : IndexController::class),
    );

    Route::get('remote', static fn () => view('remote'));

    Route::middleware('auth')->group(static function (): void {
        Route::prefix('lastfm')->group(static function (): void {
            Route::get('connect', [LastfmController::class, 'connect'])->name('lastfm.connect');
            Route::get('callback', [LastfmController::class, 'callback'])->name('lastfm.callback');
        });

        if (ITunes::used()) {
            Route::get('itunes/song/{album}', ViewSongOnITunesController::class)->name('iTunes.viewSong');
        }
    });

    Route::get('auth/google/redirect', static fn () => Socialite::driver('google')->redirect());
    Route::get('auth/google/callback', GoogleCallbackController::class);

    Route::get('dropbox/authorize/{key}', AuthorizeDropboxController::class)->name('dropbox.authorize');

    Route::middleware('audio.auth')->group(static function (): void {
        Route::get('play/{song}/{transcode?}', PlayController::class)->name('song.play');

        Route::get('radio/stream/{radioStation}', StreamRadioController::class)->name('radio.stream');

        if (config('koel.download.allow')) {
            Route::prefix('download')->group(static function (): void {
                Route::get('songs', DownloadSongsController::class);
                Route::get('album/{album}', DownloadAlbumController::class);
                Route::get('artist/{artist}', DownloadArtistController::class);
                Route::get('playlist/{playlist}', DownloadPlaylistController::class);
                Route::get('favorites', DownloadFavoritesController::class);
            });
        }
    });

    Route::get('embeds/{embed}/stream/{song}/{options}', StreamEmbedController::class)
        ->name('embeds.stream')
        ->middleware('signed', 'throttle:10,1');
});

Route::middleware('web')->prefix('demo')->group(static function (): void {
    Route::get('/new-session', NewSessionController::class)
        ->name('demo.new-session')
        ->middleware('throttle:10,1');
});
