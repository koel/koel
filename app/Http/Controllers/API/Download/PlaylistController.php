<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Playlist;
use Download;
use Exception;
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
     * @throws Exception
     *
     * @return BinaryFileResponse
     */
    public function download(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download(Download::from($playlist));
    }
}
