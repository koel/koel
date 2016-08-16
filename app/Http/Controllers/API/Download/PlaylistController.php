<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Playlist;
use Download;

class PlaylistController extends Controller
{
    /**
     * Download all songs in a playlist.
     *
     * @param Playlist $playlist
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Exception
     */
    public function download(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download(Download::from($playlist));
    }
}
