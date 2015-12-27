<?php

Route::get('login', 'Auth\AuthController@getLogin');
Route::post('login', 'Auth\AuthController@postLogin');
Route::get('logout', 'Auth\AuthController@getLogout');

Route::get('/', function () {
    return redirect('/♫');
});

Route::get('♫', ['middleware' => 'auth', function () {
    return view('index');
}]);

Route::group(['prefix' => 'api', 'middleware' => 'auth', 'namespace' => 'API'], function () {
    Route::get('/', function () {
        // Just acting as a ping service.
    });

    Route::get('data', 'DataController@index');

    Route::post('settings', 'SettingController@save');

    Route::get('{song}/play', 'SongController@play');
    Route::get('{song}/info', 'SongController@getInfo');
    Route::post('{song}/scrobble/{timestamp}', 'SongController@scrobble')->where([
        'timestamp' => '\d+',
    ]);

    Route::post('interaction/play', 'InteractionController@play');
    Route::post('interaction/like', 'InteractionController@like');
    Route::post('interaction/batch/like', 'InteractionController@batchLike');
    Route::post('interaction/batch/unlike', 'InteractionController@batchUnlike');

    Route::resource('playlist', 'PlaylistController', ['only' => ['store', 'update', 'destroy']]);
    Route::put('playlist/{playlist}/sync', 'PlaylistController@sync')->where(['playlist' => '\d+']);

    Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
    Route::put('me', 'UserController@updateProfile');

    Route::get('lastfm/connect', 'LastfmController@connect');
    Route::get('lastfm/callback', [
        'as' => 'lastfm.callback',
        'uses' => 'LastfmController@callback',
    ]);
    Route::delete('lastfm/disconnect', 'LastfmController@disconnect');
});
