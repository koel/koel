<?php

use App\Facades\ITunes;
use App\Http\Controllers\Download\AlbumController as AlbumDownloadController;
use App\Http\Controllers\Download\ArtistController as ArtistDownloadController;
use App\Http\Controllers\Download\FavoritesController as FavoritesDownloadController;
use App\Http\Controllers\Download\PlaylistController as PlaylistDownloadController;
use App\Http\Controllers\Download\SongController as SongDownloadController;
use App\Http\Controllers\ITunesController;
use App\Http\Controllers\LastfmController;
use App\Http\Controllers\PlayController;
use Illuminate\Support\Facades\Route;

Route::get('/', static fn () => view('index'));

Route::get('remote', static fn () => view('remote'));

Route::middleware('auth')->group(static function (): void {
    Route::get('play/{song}/{transcode?}/{bitrate?}', [PlayController::class, 'show'])->name('song.play');

    Route::prefix('lastfm')->group(static function (): void {
        Route::get('connect', [LastfmController::class, 'connect'])->name('lastfm.connect');
        Route::get('callback', [LastfmController::class, 'callback'])->name('lastfm.callback');
    });

    if (ITunes::used()) {
        Route::get('itunes/song/{album}', [ITunesController::class, 'viewSong'])->name('iTunes.viewSong');
    }

    Route::prefix('download')->group(static function (): void {
        Route::get('songs', [SongDownloadController::class, 'show']);
        Route::get('album/{album}', [AlbumDownloadController::class, 'show']);
        Route::get('artist/{artist}', [ArtistDownloadController::class, 'show']);
        Route::get('playlist/{playlist}', [PlaylistDownloadController::class, 'show']);
        Route::get('favorites', [FavoritesDownloadController::class, 'show']);
    });
});
