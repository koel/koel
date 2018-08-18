<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Models\Song;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FavoritesController extends Controller
{
    /**
     * Download all songs in a playlist.
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function show(Request $request)
    {
        $songs = Song::getFavorites($request->user());

        return response()->download($this->downloadService->from($songs));
    }
}
