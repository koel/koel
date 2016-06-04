<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Models\Song;
use Download;

class FavoritesController extends Controller
{
    /**
     * Download all songs in a playlist.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Request $request)
    {
        return response()->download(Download::from(Song::getFavorites($request->user())));
    }
}
