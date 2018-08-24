<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Playlist;
use Illuminate\Auth\Access\AuthorizationException;

class PlaylistController extends Controller
{
    /**
     * Download all songs in a playlist.
     *
     * @throws AuthorizationException
     */
    public function show(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download($this->downloadService->from($playlist));
    }
}
