<?php

use Illuminate\Http\Request;

Route::group(['namespace' => 'API'], function () {
    Route::post('me', 'AuthController@login');
    Route::delete('me', 'AuthController@logout');

    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::get('/ping', function () {
            // Just acting as a ping service.
        });

        Route::post('broadcasting/auth', function (Request $request) {
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
        });

        Route::get('data', 'DataController@index');

        Route::post('settings', 'SettingController@store');

        Route::get('{song}/play/{transcode?}/{bitrate?}', 'SongController@play');
        Route::post('{song}/scrobble/{timestamp}', 'ScrobbleController@store')->where([
            'timestamp' => '\d+',
        ]);
        Route::get('{song}/info', 'SongController@show');
        Route::put('songs', 'SongController@update');

        // Interaction routes
        Route::post('interaction/play', 'Interaction\PlayCountController@store');
        Route::post('interaction/like', 'Interaction\LikeController@store');
        Route::post('interaction/batch/like', 'Interaction\BatchLikeController@store');
        Route::post('interaction/batch/unlike', 'Interaction\BatchLikeController@destroy');

        // Playlist routes
        Route::resource('playlist', 'PlaylistController');
        Route::put('playlist/{playlist}/sync', 'PlaylistController@sync')->where(['playlist' => '\d+']);
        Route::get('playlist/{playlist}/songs', 'PlaylistController@getSongs')->where(['playlist' => '\d+']);

        // User and user profile routes
        Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
        Route::get('me', 'ProfileController@show');
        Route::put('me', 'ProfileController@update');

        // Last.fm-related routes
        Route::get('lastfm/connect', 'LastfmController@connect');
        Route::post('lastfm/session-key', 'LastfmController@setSessionKey');
        Route::get('lastfm/callback', [
            'as' => 'lastfm.callback',
            'uses' => 'LastfmController@callback',
        ]);
        Route::delete('lastfm/disconnect', 'LastfmController@disconnect');

        // YouTube-related routes
        if (YouTube::enabled()) {
            Route::get('youtube/search/song/{song}', 'YouTubeController@searchVideosRelatedToSong');
        }

        // Download routes
        Route::group(['prefix' => 'download', 'namespace' => 'Download'], function () {
            Route::get('songs', 'SongController@download');
            Route::get('album/{album}', 'AlbumController@download');
            Route::get('artist/{artist}', 'ArtistController@download');
            Route::get('playlist/{playlist}', 'PlaylistController@download');
            Route::get('favorites', 'FavoritesController@download');
        });

        // Info routes
        if (Lastfm::used()) {
            Route::get('album/{album}/info', 'AlbumInfoController@show');
            Route::get('artist/{artist}/info', 'ArtistInfoController@show');
        }

        // iTunes routes
        if (iTunes::used()) {
            Route::get('itunes/song/{album}', 'iTunesController@viewSong');
        }
    });

    Route::group(['middleware' => 'os.auth', 'prefix' => 'os', 'namespace' => 'ObjectStorage'], function () {
        Route::group(['prefix' => 's3', 'namespace' => 'S3'], function () {
            Route::post('song', 'SongController@put'); // we follow AWS's convention here.
            Route::delete('song', 'SongController@remove'); // and here.
        });
    });
});
