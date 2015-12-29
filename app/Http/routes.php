<?php

Route::get('/', function () {
    return view('index');
});

Route::get('{song}/play', 'PlaybackController@play');


Route::group(['prefix' => 'api', 'middleware' => 'jwt.auth', 'namespace' => 'API'], function () {
    Route::get('/', function () {
        // Just acting as a ping service.
    });

    Route::get('data', 'DataController@index');

    Route::post('settings', 'SettingController@save');
    
    Route::post('{song}/scrobble/{timestamp}', 'SongController@scrobble')->where([
        'timestamp' => '\d+',
    ]);
    Route::get('{song}/info', 'SongController@getInfo');

    Route::post('interaction/play', 'InteractionController@play');
    Route::post('interaction/like', 'InteractionController@like');
    Route::post('interaction/batch/like', 'InteractionController@batchLike');
    Route::post('interaction/batch/unlike', 'InteractionController@batchUnlike');

    Route::resource('playlist', 'PlaylistController', ['only' => ['store', 'update', 'destroy']]);
    Route::put('playlist/{playlist}/sync', 'PlaylistController@sync')->where(['playlist' => '\d+']);

    Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
    Route::post('me', 'UserController@login');
    Route::put('me', 'UserController@updateProfile');

    Route::get('lastfm/connect', 'LastfmController@connect');
    Route::get('lastfm/callback', [
        'as' => 'lastfm.callback',
        'uses' => 'LastfmController@callback',
    ]);
    Route::delete('lastfm/disconnect', 'LastfmController@disconnect');
});
