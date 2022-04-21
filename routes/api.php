<?php

use App\Facades\YouTube;
use App\Http\Controllers\API\AlbumCoverController;
use App\Http\Controllers\API\AlbumThumbnailController;
use App\Http\Controllers\API\ArtistImageController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DataController;
use App\Http\Controllers\API\Interaction\BatchLikeController;
use App\Http\Controllers\API\Interaction\LikeController;
use App\Http\Controllers\API\Interaction\PlayCountController;
use App\Http\Controllers\API\Interaction\RecentlyPlayedController;
use App\Http\Controllers\API\LastfmController;
use App\Http\Controllers\API\MediaInformation\AlbumController as AlbumInformationController;
use App\Http\Controllers\API\MediaInformation\ArtistController as ArtistInformationController;
use App\Http\Controllers\API\MediaInformation\SongController as SongInformationController;
use App\Http\Controllers\API\ObjectStorage\S3\SongController as S3SongController;
use App\Http\Controllers\API\PlaylistController;
use App\Http\Controllers\API\PlaylistSongController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ScrobbleController;
use App\Http\Controllers\API\Search\ExcerptSearchController;
use App\Http\Controllers\API\Search\SongSearchController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\SongController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\YouTubeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Pusher\Pusher;

Route::post('me', [AuthController::class, 'login'])->name('auth.login');
Route::delete('me', [AuthController::class, 'logout']);

Route::middleware('auth')->group(static function (): void {
    Route::get('ping', static fn () => null);

    Route::post('broadcasting/auth', static function (Request $request) {
        $pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'encrypted' => true,
            ]
        );

        return $pusher->socket_auth($request->channel_name, $request->socket_id);
    })->name('broadcasting.auth');

    Route::get('data', [DataController::class, 'index']);

    Route::put('settings', [SettingController::class, 'update']);

    Route::post('{song}/scrobble', [ScrobbleController::class, 'store']);
    Route::put('songs', [SongController::class, 'update']);

    Route::apiResource('upload', UploadController::class)->only('store');

    // Interaction routes
    Route::post('interaction/play', [PlayCountController::class, 'store']);
    Route::post('interaction/like', [LikeController::class, 'store']);
    Route::post('interaction/batch/like', [BatchLikeController::class, 'store']);
    Route::post('interaction/batch/unlike', [BatchLikeController::class, 'destroy']);
    Route::get('interaction/recently-played/{count?}', [RecentlyPlayedController::class, 'index'])->where([
        'count' => '\d+',
    ]);

    // Playlist routes
    Route::apiResource('playlist', PlaylistController::class);

    Route::put('playlist/{playlist}/sync', [PlaylistSongController::class, 'update']); // @deprecated
    Route::put('playlist/{playlist}/songs', [PlaylistSongController::class, 'update']);
    Route::get('playlist/{playlist}/songs', [PlaylistSongController::class, 'index']);

    // User and user profile routes
    Route::apiResource('user', UserController::class)->only('store', 'update', 'destroy');
    Route::get('me', [ProfileController::class, 'show']);
    Route::put('me', [ProfileController::class, 'update']);

    // Last.fm-related routes
    Route::post('lastfm/session-key', [LastfmController::class, 'setSessionKey']);
    Route::delete('lastfm/disconnect', [LastfmController::class, 'disconnect'])->name('lastfm.disconnect');

    // YouTube-related routes
    if (YouTube::enabled()) {
        Route::get('youtube/search/song/{song}', [YouTubeController::class, 'searchVideosRelatedToSong']);
    }

    // Media information routes
    Route::get('album/{album}/info', [AlbumInformationController::class, 'show']);
    Route::get('artist/{artist}/info', [ArtistInformationController::class, 'show']);
    Route::get('song/{song}/info', [SongInformationController::class, 'show']);

    // Cover/image upload routes
    Route::put('album/{album}/cover', [AlbumCoverController::class, 'update']);
    Route::put('artist/{artist}/image', [ArtistImageController::class, 'update']);
    Route::get('album/{album}/thumbnail', [AlbumThumbnailController::class, 'show']);

    // Search routes
    Route::prefix('search')->group(static function (): void {
        Route::get('/', [ExcerptSearchController::class, 'index']);
        Route::get('songs', [SongSearchController::class, 'index']);
    });
});

// Object-storage (S3) routes
Route::middleware('os.auth')->prefix('os/s3')->group(static function (): void {
    Route::post('song', [S3SongController::class, 'put'])->name('s3.song.put'); // we follow AWS's convention here.
    Route::delete('song', [S3SongController::class, 'remove'])->name('s3.song.remove'); // and here.
});
