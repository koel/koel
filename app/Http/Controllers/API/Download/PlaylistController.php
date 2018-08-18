<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Playlist;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlaylistController extends Controller
{
    /**
     * Download all songs in a playlist.
     *
     * @param Playlist $playlist
     *
     * @throws AuthorizationException
     *
     * @return BinaryFileResponse
     */
    public function show(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download($this->downloadService->from($playlist));
    }
}
