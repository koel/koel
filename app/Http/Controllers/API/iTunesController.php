<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\ViewSongOnITunesRequest;
use App\Models\Album;
use App\Services\iTunesService;
use Illuminate\Http\RedirectResponse;

class iTunesController extends Controller
{
    private $iTunesService;

    public function __construct(iTunesService $iTunesService)
    {
        $this->iTunesService = $iTunesService;
    }

    /**
     * View a song on iTunes store.
     *
     * @param ViewSongOnITunesRequest $request
     * @param Album                   $album
     *
     * @return RedirectResponse
     */
    public function viewSong(ViewSongOnITunesRequest $request, Album $album)
    {
        $url = $this->iTunesService->getTrackUrl($request->q, $album->name, $album->artist->name);
        abort_unless($url, 404, "Koel can't find such a song on iTunes Store.");

        return redirect($url);
    }
}
