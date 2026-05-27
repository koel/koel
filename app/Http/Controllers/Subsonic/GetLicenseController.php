<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\SubsonicResponse;
use Illuminate\Http\Request;

class GetLicenseController extends Controller
{
    public function __invoke(Request $request): SubsonicResponse
    {
        return SubsonicResponse::ok([
            'license' => [
                'valid' => true,
                'email' => $request->user()->email,
            ],
        ]);
    }
}
