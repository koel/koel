<?php

Route::get('/', function () {
    return view('index');
});

Route::get('/music', function () {
	    return view('music');
});

// Some backward compatibilities.
Route::get('/♫', function () {
    return redirect('/');
});
