<?php

Route::get('/', function () {
    return view('index');
});

// Some backward compatibilities.
Route::get('/♫', function () {
    return redirect('/');
});

Route::group(['prefix' => 'api', 'namespace' => 'API'], function () {
    Route::post('me', 'AuthController@login');
    Route::delete('me', 'AuthController@logout');

    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::get('/', function () {
            // Just acting as a ping service.
        });

        Route::get('data', 'DataController@index');

        Route::post('settings', 'SettingController@store');
        Route::post('syncLibrary/{force?}', 'SyncController@sync');

        Route::get('{song}/play/{transcode?}/{bitrate?}', 'SongController@play');
        Route::post('{song}/scrobble/{timestamp}', 'ScrobbleController@store')->where([
            'timestamp' => '\d+',
        ]);
        Route::get('{song}/info', 'SongController@show');
        Route::put('songs', 'SongController@update');

        // Interaction routes
        Route::post('interaction/play', 'InteractionController@play');
        Route::post('interaction/like', 'InteractionController@like');
        Route::post('interaction/batch/like', 'InteractionController@batchLike');
        Route::post('interaction/batch/unlike', 'InteractionController@batchUnlike');

        // Playlist routes
        Route::resource('playlist', 'PlaylistController');
        Route::put('playlist/{playlist}/sync', 'PlaylistController@sync')->where(['playlist' => '\d+']);

        // User and user profile routes
        Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
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
            Route::get('album/{album}/info', 'AlbumController@getInfo');
            Route::get('artist/{artist}/info', 'ArtistController@getInfo');
        }
    });

    Route::group(['middleware' => 'os.auth', 'prefix' => 'os', 'namespace' => 'ObjectStorage'], function () {
        Route::group(['prefix' => 's3', 'namespace' => 'S3'], function () {
            Route::post('song', 'SongController@put'); // we follow AWS's convention here.
            Route::delete('song', 'SongController@remove'); // and here.
        });
    });
});
