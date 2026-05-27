<?php

use App\Http\Controllers\Subsonic\GetAlbumController;
use App\Http\Controllers\Subsonic\GetAlbumList2Controller;
use App\Http\Controllers\Subsonic\GetArtistController;
use App\Http\Controllers\Subsonic\GetArtistsController;
use App\Http\Controllers\Subsonic\GetGenresController;
use App\Http\Controllers\Subsonic\GetLicenseController;
use App\Http\Controllers\Subsonic\GetMusicFoldersController;
use App\Http\Controllers\Subsonic\GetSongController;
use App\Http\Controllers\Subsonic\PingController;
use App\Http\Controllers\Subsonic\Search3Controller;
use App\Http\Middleware\SubsonicAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('rest')
    ->middleware(SubsonicAuth::class)
    ->group(static function (): void {
        Route::match(['get', 'post'], 'ping.view', PingController::class);
        Route::match(['get', 'post'], 'getLicense.view', GetLicenseController::class);
        Route::match(['get', 'post'], 'getMusicFolders.view', GetMusicFoldersController::class);
        Route::match(['get', 'post'], 'getArtists.view', GetArtistsController::class);
        Route::match(['get', 'post'], 'getArtist.view', GetArtistController::class);
        Route::match(['get', 'post'], 'getAlbum.view', GetAlbumController::class);
        Route::match(['get', 'post'], 'getSong.view', GetSongController::class);
        Route::match(['get', 'post'], 'getGenres.view', GetGenresController::class);
        Route::match(['get', 'post'], 'search3.view', Search3Controller::class);
        Route::match(['get', 'post'], 'getAlbumList2.view', GetAlbumList2Controller::class);
    });
