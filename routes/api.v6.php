<?php

use App\Facades\YouTube;
use App\Http\Controllers\API\PlaylistController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\V6\API\AlbumController;
use App\Http\Controllers\V6\API\AlbumSongController;
use App\Http\Controllers\V6\API\ArtistController;
use App\Http\Controllers\V6\API\ArtistSongController;
use App\Http\Controllers\V6\API\DataController;
use App\Http\Controllers\V6\API\ExcerptSearchController;
use App\Http\Controllers\V6\API\FavoriteSongController;
use App\Http\Controllers\V6\API\FetchAlbumInformationController;
use App\Http\Controllers\V6\API\FetchArtistInformationController;
use App\Http\Controllers\V6\API\OverviewController;
use App\Http\Controllers\V6\API\PlayCountController;
use App\Http\Controllers\V6\API\PlaylistSongController;
use App\Http\Controllers\V6\API\QueueController;
use App\Http\Controllers\V6\API\RecentlyPlayedSongController;
use App\Http\Controllers\V6\API\SongController;
use App\Http\Controllers\V6\API\SongSearchController;
use App\Http\Controllers\V6\API\YouTubeSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware('api')->group(static function (): void {
    Route::middleware('auth')->group(static function (): void {
        Route::get('overview', [OverviewController::class, 'index']);
        Route::get('data', [DataController::class, 'index']);

        Route::apiResource('albums', AlbumController::class);
        Route::apiResource('albums.songs', AlbumSongController::class);
        Route::apiResource('artists', ArtistController::class);
        Route::apiResource('artists.songs', ArtistSongController::class);

        Route::get('albums/{album}/information', FetchAlbumInformationController::class);
        Route::get('artists/{artist}/information', FetchArtistInformationController::class);

        Route::apiResource('playlists', PlaylistController::class);
        Route::apiResource('playlists.songs', PlaylistSongController::class);
        Route::post('playlists/{playlist}/songs', [PlaylistSongController::class, 'add']);
        Route::delete('playlists/{playlist}/songs', [PlaylistSongController::class, 'remove']);

        Route::apiResource('songs', SongController::class)->where(['song' => '[a-f0-9]{32}']);
        Route::get('songs/recently-played', [RecentlyPlayedSongController::class, 'index']);
        Route::get('songs/favorite', [FavoriteSongController::class, 'index']);
        
        Route::apiResource('users', UserController::class);

        Route::get('search', ExcerptSearchController::class);
        Route::get('search/songs', SongSearchController::class);

        Route::get('queue/fetch', [QueueController::class, 'fetchSongs']);

        if (YouTube::enabled()) {
            Route::get('youtube/search/song/{song}', YouTubeSearchController::class);
        }

        Route::post('interaction/play', [PlayCountController::class, 'store']);
    });
});
