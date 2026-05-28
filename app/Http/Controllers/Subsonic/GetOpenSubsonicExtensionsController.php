<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\SubsonicResponse;

class GetOpenSubsonicExtensionsController extends Controller
{
    /**
     * @see https://opensubsonic.netlify.app/docs/endpoints/getopensubsonicextensions/
     * @see https://opensubsonic.netlify.app/docs/extensions/
     */
    private const array EXTENSIONS = [
        ['name' => 'songLyrics', 'versions' => [1]],
        ['name' => 'transcodeOffset', 'versions' => [1]],
    ];

    public function __invoke()
    {
        return SubsonicResponse::ok(['openSubsonicExtensions' => self::EXTENSIONS]);
    }
}
