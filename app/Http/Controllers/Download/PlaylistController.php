<?php

namespace App\Http\Controllers\Download;

use App\Models\Playlist;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * @group 6. Download
 */
class PlaylistController extends Controller
{
    /**
     * Download a whole playlist
     *
     * @response []
     *
     * @throws AuthorizationException
     */
    public function show(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download($this->downloadService->from($playlist));
    }
}
