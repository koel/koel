<?php

use App\Facades\ITunes;
use App\Http\Controllers\Download\DownloadAlbumController;
use App\Http\Controllers\Download\DownloadArtistController;
use App\Http\Controllers\Download\DownloadFavoritesController;
use App\Http\Controllers\Download\DownloadPlaylistController;
use App\Http\Controllers\Download\DownloadSongsController;
use App\Http\Controllers\LastfmController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ViewSongOnITunesController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(static function (): void {
    Route::get('/', static fn () => view('index'));

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

    Route::middleware('audio.auth')->group(static function (): void {
        Route::get('play/{song}/{transcode?}/{bitrate?}', PlayController::class)->name('song.play');

        Route::prefix('download')->group(static function (): void {
            Route::get('songs', DownloadSongsController::class);
            Route::get('album/{album}', DownloadAlbumController::class);
            Route::get('artist/{artist}', DownloadArtistController::class);
            Route::get('playlist/{playlist}', DownloadPlaylistController::class);
            Route::get('favorites', DownloadFavoritesController::class);
        });
    });
});
