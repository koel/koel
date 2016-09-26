<?php

Route::get('/', function () {
    return view('index');
});

// Some backward compatibilities.
Route::get('/♫', function () {
    return redirect('/');
});
