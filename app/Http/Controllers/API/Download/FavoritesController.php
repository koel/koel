<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Models\Song;
use Download;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FavoritesController extends Controller
{
    /**
     * Download all songs in a playlist.
     *
     * @param Request $request
     *
     * @throws Exception
     *
     * @return BinaryFileResponse
     */
    public function download(Request $request)
    {
        return response()->download(Download::from(Song::getFavorites($request->user())));
    }
}
