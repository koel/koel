<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\SubsonicResponse;

class PingController extends Controller
{
    public function __invoke()
    {
        return SubsonicResponse::ok();
    }
}
