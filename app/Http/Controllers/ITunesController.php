<?php

namespace App\Http\Controllers;

use App\Http\Requests\API\ViewSongOnITunesRequest;
use App\Models\Album;
use App\Services\iTunesService;
use App\Services\TokenManager;
use Illuminate\Http\Response;

class iTunesController extends Controller
{
    private $iTunesService;
    private $tokenManager;

    public function __construct(iTunesService $iTunesService, TokenManager $tokenManager)
    {
        $this->iTunesService = $iTunesService;
        $this->tokenManager = $tokenManager;
    }

    public function viewSong(ViewSongOnITunesRequest $request, Album $album)
    {
        abort_unless(
            (bool) $this->tokenManager->getUserFromPlainTextToken($request->api_token),
            Response::HTTP_UNAUTHORIZED
        );

        $url = $this->iTunesService->getTrackUrl($request->q, $album->name, $album->artist->name);
        abort_unless($url, 404, "Koel can't find such a song on iTunes Store.");

        return redirect($url);
    }
}
