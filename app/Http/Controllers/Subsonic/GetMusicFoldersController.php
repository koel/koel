<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\SubsonicResponse;

class GetMusicFoldersController extends Controller
{
    public function __invoke()
    {
        return SubsonicResponse::ok([
            'musicFolders' => [
                'musicFolder' => [
                    ['id' => 1, 'name' => 'Music'],
                ],
            ],
        ]);
    }
}
