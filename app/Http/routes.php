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
    get('{id}/info', 'SongController@getInfo')->where('id', '[a-f0-9]{32}');
    post('{id}/scrobble/{timestamp}', 'SongController@scrobble')->where([
        'id' => '[a-f0-9]{32}',
        'timestamp' => '\d+',
    ]);

    post('interaction/play', 'InteractionController@play');
    post('interaction/like', 'InteractionController@like');
    post('interaction/batch/like', 'InteractionController@batchLike');
    post('interaction/batch/unlike', 'InteractionController@batchUnlike');

    resource('playlist', 'PlaylistController', ['only' => ['store', 'update', 'destroy']]);
    put('playlist/{playlist}/sync', 'PlaylistController@sync')->where(['playlist' => '\d+']);

    resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
    put('me', 'UserController@updateProfile');

    get('lastfm/connect', 'LastfmController@connect');
    get('lastfm/callback', [
        'as' => 'lastfm.callback',
        'uses' => 'LastfmController@callback',
    ]);
    delete('lastfm/disconnect', 'LastfmController@disconnect');
});
