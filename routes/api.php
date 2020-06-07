<?php

use Illuminate\Http\Request;

Route::group(['namespace' => 'API'], function () {
    Route::post('me', 'AuthController@login')->name('auth.login');
    Route::delete('me', 'AuthController@logout');

    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::get('/ping', function () {
            // Only  acting as a ping service.
        });

        Route::post('broadcasting/auth', function (Request $request) {
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

        Route::get('{song}/play/{transcode?}/{bitrate?}', 'SongController@play')->name('song.play');
        Route::post('{song}/scrobble/{timestamp}', 'ScrobbleController@store')->where([
            'timestamp' => '\d+',
        ]);
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
        Route::get('lastfm/connect', 'LastfmController@connect');
        Route::post('lastfm/session-key', 'LastfmController@setSessionKey');
        Route::get('lastfm/callback', 'LastfmController@callback')->name('lastfm.callback');
        Route::delete('lastfm/disconnect', 'LastfmController@disconnect')->name('lastfm.disconnect');

        // YouTube-related routes
        if (YouTube::enabled()) {
            Route::get('youtube/search/song/{song}', 'YouTubeController@searchVideosRelatedToSong');
        }

        // Download routes
        Route::group(['prefix' => 'download', 'namespace' => 'Download'], function () {
            Route::get('songs', 'SongController@show');
            Route::get('album/{album}', 'AlbumController@show');
            Route::get('artist/{artist}', 'ArtistController@show');
            Route::get('playlist/{playlist}', 'PlaylistController@show');
            Route::get('favorites', 'FavoritesController@show');
        });

        // Info routes
        Route::group(['namespace' => 'MediaInformation'], function () {
            Route::get('album/{album}/info', 'AlbumController@show');
            Route::get('artist/{artist}/info', 'ArtistController@show');
            Route::get('{song}/info', 'SongController@show')->name('song.show.deprecated'); // backward compat
            Route::get('song/{song}/info', 'SongController@show');
        });

        // Cover/image upload routes
        Route::put('album/{album}/cover', 'AlbumCoverController@update');
        Route::put('artist/{artist}/image', 'ArtistImageController@update');

        // iTunes routes
        if (iTunes::used()) {
            Route::get('itunes/song/{album}', 'iTunesController@viewSong')->name('iTunes.viewSong');
        }
    });

    Route::group(['middleware' => 'os.auth', 'prefix' => 'os', 'namespace' => 'ObjectStorage'], function () {
        Route::group(['prefix' => 's3', 'namespace' => 'S3'], function () {
            Route::post('song', 'SongController@put')->name('s3.song.put'); // we follow AWS's convention here.
            Route::delete('song', 'SongController@remove')->name('s3.song.remove'); // and here.
        });
    });
});
