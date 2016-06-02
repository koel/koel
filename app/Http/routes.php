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

        // Download routes
        Route::group(['prefix' => 'download', 'namespace' => 'Download'], function () {
            Route::get('songs', 'SongController@download');
        });
    });
});
