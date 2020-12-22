<?php

use App\Facades\ITunes;
use Illuminate\Support\Facades\Route;

Route::get('/', static function () {
    return view('index');
});

Route::get('/remote', static function () {
    return view('remote');
});

Route::group(['middleware' => 'auth'], static function (): void {
    Route::get('play/{song}/{transcode?}/{bitrate?}', 'PlayController@show')
        ->name('song.play');

    Route::group(['prefix' => 'lastfm'], static function (): void {
        Route::get('connect', 'LastfmController@connect')->name('lastfm.connect');
        Route::get('callback', 'LastfmController@callback')->name('lastfm.callback');
    });

    if (ITunes::used()) {
        Route::get('itunes/song/{album}', 'ITunesController@viewSong')->name('iTunes.viewSong');
    }

    Route::group(['prefix' => 'download', 'namespace' => 'Download'], static function (): void {
        Route::get('songs', 'SongController@show');
        Route::get('album/{album}', 'AlbumController@show');
        Route::get('artist/{artist}', 'ArtistController@show');
        Route::get('playlist/{playlist}', 'PlaylistController@show');
        Route::get('favorites', 'FavoritesController@show');
    });
});
