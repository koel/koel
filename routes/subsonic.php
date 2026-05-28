<?php

use App\Http\Controllers\Subsonic\CreatePlaylistController;
use App\Http\Controllers\Subsonic\DeletePlaylistController;
use App\Http\Controllers\Subsonic\GetAlbumController;
use App\Http\Controllers\Subsonic\GetAlbumList2Controller;
use App\Http\Controllers\Subsonic\GetArtistController;
use App\Http\Controllers\Subsonic\GetArtistsController;
use App\Http\Controllers\Subsonic\GetCoverArtController;
use App\Http\Controllers\Subsonic\GetGenresController;
use App\Http\Controllers\Subsonic\GetLicenseController;
use App\Http\Controllers\Subsonic\GetMusicFoldersController;
use App\Http\Controllers\Subsonic\GetPlaylistController;
use App\Http\Controllers\Subsonic\GetPlaylistsController;
use App\Http\Controllers\Subsonic\GetSongController;
use App\Http\Controllers\Subsonic\PingController;
use App\Http\Controllers\Subsonic\ScrobbleController;
use App\Http\Controllers\Subsonic\Search3Controller;
use App\Http\Controllers\Subsonic\SetRatingController;
use App\Http\Controllers\Subsonic\StarController;
use App\Http\Controllers\Subsonic\StreamController;
use App\Http\Controllers\Subsonic\UnstarController;
use App\Http\Controllers\Subsonic\UpdatePlaylistController;
use App\Http\Middleware\NormalizeSubsonicArrayParams;
use App\Http\Middleware\SubsonicAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('rest')
    ->middleware([NormalizeSubsonicArrayParams::class, SubsonicAuth::class])
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
        Route::match(['get', 'post'], 'stream.view', StreamController::class);
        Route::match(['get', 'post'], 'getCoverArt.view', GetCoverArtController::class);
        Route::match(['get', 'post'], 'getPlaylists.view', GetPlaylistsController::class);
        Route::match(['get', 'post'], 'getPlaylist.view', GetPlaylistController::class);
        Route::match(['get', 'post'], 'createPlaylist.view', CreatePlaylistController::class);
        Route::match(['get', 'post'], 'updatePlaylist.view', UpdatePlaylistController::class);
        Route::match(['get', 'post'], 'deletePlaylist.view', DeletePlaylistController::class);
        Route::match(['get', 'post'], 'scrobble.view', ScrobbleController::class);
        Route::match(['get', 'post'], 'star.view', StarController::class);
        Route::match(['get', 'post'], 'unstar.view', UnstarController::class);
        Route::match(['get', 'post'], 'setRating.view', SetRatingController::class);
    });
