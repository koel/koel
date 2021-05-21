<?php

use Illuminate\Http\Request;

Route::group(['namespace' => 'API'], static function (): void {
    Route::post('me', 'AuthController@login')->name('auth.login');
    Route::delete('me', 'AuthController@logout');

    Route::group(['middleware' => 'auth'], static function (): void {
        Route::get('/ping', static function (): void {
            // Only  acting as a ping service.
        });

        Route::post('broadcasting/auth', static function (Request $request) {
            $pusher = new Pusher\Pusher(
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

        Route::get('data', 'DataController@index');

        Route::post('settings', 'SettingController@store');

        Route::post('{song}/scrobble', 'ScrobbleController@store');
        Route::put('songs', 'SongController@update');

        Route::resource('upload', 'UploadController');

        // Interaction routes
        Route::post('interaction/play', 'Interaction\PlayCountController@store');
        Route::post('interaction/like', 'Interaction\LikeController@store');
        Route::post('interaction/batch/like', 'Interaction\BatchLikeController@store');
        Route::post('interaction/batch/unlike', 'Interaction\BatchLikeController@destroy');
        Route::get('interaction/recently-played/{count?}', 'Interaction\RecentlyPlayedController@index')->where([
            'count' => '\d+',
        ]);

        // Playlist routes
        Route::resource('playlist', 'PlaylistController')->only(['index', 'store', 'update', 'destroy']);
        Route::put('playlist/{playlist}/sync', 'PlaylistController@sync')->where(['playlist' => '\d+']);
        Route::get('playlist/{playlist}/songs', 'PlaylistController@getSongs')->where(['playlist' => '\d+']);

        // User and user profile routes
        Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
        Route::get('me', 'ProfileController@show');
        Route::put('me', 'ProfileController@update');

        // Last.fm-related routes
        Route::post('lastfm/session-key', 'LastfmController@setSessionKey');
        Route::delete('lastfm/disconnect', 'LastfmController@disconnect')->name('lastfm.disconnect');

        // YouTube-related routes
        if (YouTube::enabled()) {
            Route::get('youtube/search/song/{song}', 'YouTubeController@searchVideosRelatedToSong');
        }

        // Info routes
        Route::group(['namespace' => 'MediaInformation'], static function (): void {
            Route::get('album/{album}/info', 'AlbumController@show');
            Route::get('artist/{artist}/info', 'ArtistController@show');
            Route::get('{song}/info', 'SongController@show')->name('song.show.deprecated'); // backward compat
            Route::get('song/{song}/info', 'SongController@show');
        });

        // Cover/image upload routes
        Route::put('album/{album}/cover', 'AlbumCoverController@update');
        Route::put('artist/{artist}/image', 'ArtistImageController@update');

        Route::get('album/{album}/thumbnail', 'AlbumThumbnailController@show');

        Route::group(['namespace' => 'Search', 'prefix' => 'search'], static function (): void {
            Route::get('/', 'ExcerptSearchController@index');
            Route::get('songs', 'SongSearchController@index');
        });
    });

    Route::group([
        'middleware' => 'os.auth',
        'prefix' => 'os',
        'namespace' => 'ObjectStorage',
    ], static function (): void {
        Route::group(['prefix' => 's3', 'namespace' => 'S3'], static function (): void {
            Route::post('song', 'SongController@put')->name('s3.song.put'); // we follow AWS's convention here.
            Route::delete('song', 'SongController@remove')->name('s3.song.remove'); // and here.
        });
    });
});
