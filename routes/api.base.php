<?php

use App\Facades\YouTube;
use App\Http\Controllers\API\AlbumController;
use App\Http\Controllers\API\AlbumCoverController;
use App\Http\Controllers\API\AlbumSongController;
use App\Http\Controllers\API\AlbumThumbnailController;
use App\Http\Controllers\API\ArtistAlbumController;
use App\Http\Controllers\API\ArtistController;
use App\Http\Controllers\API\ArtistImageController;
use App\Http\Controllers\API\ArtistSongController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DataController;
use App\Http\Controllers\API\DemoCreditController;
use App\Http\Controllers\API\ExcerptSearchController;
use App\Http\Controllers\API\FavoriteSongController;
use App\Http\Controllers\API\FetchAlbumInformationController;
use App\Http\Controllers\API\FetchArtistInformationController;
use App\Http\Controllers\API\FetchRandomSongsInGenreController;
use App\Http\Controllers\API\GenreController;
use App\Http\Controllers\API\GenreSongController;
use App\Http\Controllers\API\Interaction\BatchLikeController;
use App\Http\Controllers\API\Interaction\LikeController;
use App\Http\Controllers\API\Interaction\PlayCountController;
use App\Http\Controllers\API\LastfmController;
use App\Http\Controllers\API\ObjectStorage\S3\SongController as S3SongController;
use App\Http\Controllers\API\OverviewController;
use App\Http\Controllers\API\PlaylistController;
use App\Http\Controllers\API\PlaylistFolderController;
use App\Http\Controllers\API\PlaylistFolderPlaylistController;
use App\Http\Controllers\API\PlaylistSongController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\QueueController;
use App\Http\Controllers\API\RecentlyPlayedSongController;
use App\Http\Controllers\API\ScrobbleController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\SongController;
use App\Http\Controllers\API\SongSearchController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\YouTubeController;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Pusher\Pusher;

Route::prefix('api')->middleware('api')->group(static function (): void {
    Route::post('me', [AuthController::class, 'login'])->name('auth.login');
    Route::delete('me', [AuthController::class, 'logout']);

    Route::get('ping', static fn () => null);

    Route::middleware('auth')->group(static function (): void {
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

        Route::get('overview', [OverviewController::class, 'index']);
        Route::get('data', [DataController::class, 'index']);

        Route::get('queue/fetch', [QueueController::class, 'fetchSongs']);

        Route::put('settings', [SettingController::class, 'update']);

        Route::apiResource('albums', AlbumController::class);
        Route::apiResource('albums.songs', AlbumSongController::class);

        Route::apiResource('artists', ArtistController::class);
        Route::apiResource('artists.albums', ArtistAlbumController::class);
        Route::apiResource('artists.songs', ArtistSongController::class);

        Route::post('songs/{song}/scrobble', [ScrobbleController::class, 'store'])->where(['song' => Song::ID_REGEX]);

        Route::apiResource('songs', SongController::class)
            ->except('update', 'destroy')
            ->where(['song' => Song::ID_REGEX]);

        Route::put('songs', [SongController::class, 'update']);
        Route::delete('songs', [SongController::class, 'destroy']);

        Route::post('upload', UploadController::class);

        // Interaction routes
        Route::post('interaction/play', [PlayCountController::class, 'store']);
        Route::post('interaction/like', [LikeController::class, 'store']);
        Route::post('interaction/batch/like', [BatchLikeController::class, 'store']);
        Route::post('interaction/batch/unlike', [BatchLikeController::class, 'destroy']);

        Route::get('songs/recently-played', [RecentlyPlayedSongController::class, 'index']);
        Route::get('songs/favorite', [FavoriteSongController::class, 'index']);

        Route::apiResource('playlist-folders', PlaylistFolderController::class);
        Route::apiResource('playlist-folders.playlists', PlaylistFolderPlaylistController::class)->except('destroy');
        Route::delete(
            'playlist-folders/{playlistFolder}/playlists',
            [PlaylistFolderPlaylistController::class, 'destroy']
        );

        // Playlist routes
        Route::apiResource('playlists', PlaylistController::class);
        Route::apiResource('playlists.songs', PlaylistSongController::class)->except('destroy');
        Route::delete('playlists/{playlist}/songs', [PlaylistSongController::class, 'destroy']);

        Route::get('genres/{genre}/songs', GenreSongController::class)->where('genre', '.*');
        Route::get('genres/{genre}/songs/random', FetchRandomSongsInGenreController::class)->where('genre', '.*');
        Route::apiResource('genres', GenreController::class)->where(['genre' => '.*']);

        Route::apiResource('users', UserController::class);

        // User and user profile routes
        Route::apiResource('user', UserController::class);
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
        Route::get('albums/{album}/information', FetchAlbumInformationController::class);
        Route::get('artists/{artist}/information', FetchArtistInformationController::class);

        // Cover/image upload routes
        Route::put('album/{album}/cover', [AlbumCoverController::class, 'update']);
        Route::put('artist/{artist}/image', [ArtistImageController::class, 'update']);
        Route::get('album/{album}/thumbnail', [AlbumThumbnailController::class, 'show']);

        Route::get('search', ExcerptSearchController::class);
        Route::get('search/songs', SongSearchController::class);
    });

    // Object-storage (S3) routes
    Route::middleware('os.auth')->prefix('os/s3')->group(static function (): void {
        Route::post('song', [S3SongController::class, 'put'])->name('s3.song.put'); // we follow AWS's convention here.
        Route::delete('song', [S3SongController::class, 'remove'])->name('s3.song.remove'); // and here.
    });

    Route::get('demo/credits', [DemoCreditController::class, 'index']);
});
