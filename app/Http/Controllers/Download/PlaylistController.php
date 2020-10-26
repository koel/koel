<?php

namespace App\Http\Controllers\Download;

use App\Models\Playlist;

class PlaylistController extends Controller
{
    public function show(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download($this->downloadService->from($playlist));
    }
}
