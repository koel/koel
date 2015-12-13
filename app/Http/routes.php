<?php

get('login', 'Auth\AuthController@getLogin');
post('login', 'Auth\AuthController@postLogin');
get('logout', 'Auth\AuthController@getLogout');

get('/', function () {
    return redirect('/♫');
});

get('♫', ['middleware' => 'auth', function () {
    return view('index');
}]);

Route::group(['prefix' => 'api', 'middleware' => 'auth', 'namespace' => 'API'], function () {
    get('/', function () {
        // Just acting as a ping service.
    });

    get('data', 'DataController@index');

    post('settings', 'SettingController@save');

    get('{id}/play', 'SongController@play')->where('id', '[a-f0-9]{32}');

    get('{id}/lyrics', 'SongController@getLyrics')->where('id', '[a-f0-9]{32}');

    post('interaction/play', 'InteractionController@play');
    post('interaction/like', 'InteractionController@like');
    post('interaction/batch/like', 'InteractionController@batchLike');
    post('interaction/batch/unlike', 'InteractionController@batchUnlike');

    resource('playlist', 'PlaylistController');
    put('playlist/{id}/sync', 'PlaylistController@sync')->where(['id' => '\d+']);

    resource('user', 'UserController');
    put('me', 'UserController@updateProfile');
});
