<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Models\Playlist;
use Download;

class PlaylistController extends Controller
{
    /**
     * Download all songs in a playlist.
     *
     * @param Request  $request
     * @param Playlist $playlist
     *
     * @return
     */
    public function download(Request $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download(Download::from($playlist));
    }
}
