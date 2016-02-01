<?php

Route::get('/', function () {
    return view('index');
});

// Some backward compatibilities.
Route::get('/♫', function () {
    return redirect('/');
});

Route::group(['prefix' => 'api', 'namespace' => 'API'], function () {

    Route::post('me', 'UserController@login');

    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::get('/', function () {
            // Just acting as a ping service.
        });

        Route::get('data', 'DataController@index');

        Route::post('settings', 'SettingController@save');

        Route::get('{song}/play', 'SongController@play');
        Route::post('{song}/scrobble/{timestamp}', 'SongController@scrobble')->where([
            'timestamp' => '\d+',
        ]);
        Route::get('{song}/info', 'SongController@getInfo');

        Route::post('interaction/play', 'InteractionController@play');
        Route::post('interaction/like', 'InteractionController@like');
        Route::post('interaction/batch/like', 'InteractionController@batchLike');
        Route::post('interaction/batch/unlike', 'InteractionController@batchUnlike');

        Route::resource('playlist', 'PlaylistController');
        Route::put('playlist/{playlist}/sync', 'PlaylistController@sync')->where(['playlist' => '\d+']);

        Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
        Route::put('me', 'UserController@updateProfile');
        Route::delete('me', 'UserController@logout');

        Route::get('lastfm/connect', 'LastfmController@connect');
        Route::post('lastfm/session-key', 'LastfmController@setSessionKey');

        Route::get('lastfm/callback', [
            'as' => 'lastfm.callback',
            'uses' => 'LastfmController@callback',
        ]);
        Route::delete('lastfm/disconnect', 'LastfmController@disconnect');
    });
});
