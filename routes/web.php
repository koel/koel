<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

// Some backward compatibilities.
Route::get('/♫', function () {
    return redirect('/');
});

Route::get('/remote', function () {
    return view('remote');
});

Route::get('/lastfm/connect', 'API\LastfmController@connect')
    ->name('lastfm.connect');

Route::get('/lastfm/callback', 'API\LastfmController@callback')
    ->name('lastfm.callback');
