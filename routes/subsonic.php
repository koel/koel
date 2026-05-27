<?php

use App\Http\Controllers\Subsonic\GetLicenseController;
use App\Http\Controllers\Subsonic\PingController;
use App\Http\Middleware\SubsonicAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('rest')
    ->middleware(SubsonicAuth::class)
    ->group(static function (): void {
        Route::match(['get', 'post'], 'ping.view', PingController::class);
        Route::match(['get', 'post'], 'getLicense.view', GetLicenseController::class);
    });
