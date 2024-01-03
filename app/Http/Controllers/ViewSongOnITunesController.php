<?php

namespace App\Http\Controllers;

use App\Http\Requests\API\ViewSongOnITunesRequest;
use App\Models\Album;
use App\Services\ITunesService;
use App\Services\TokenManager;
use Illuminate\Http\Response;

class ViewSongOnITunesController extends Controller
{
    public function __invoke(
        ViewSongOnITunesRequest $request,
        ITunesService $iTunesService,
        TokenManager $tokenManager,
        Album $album
    ) {
        abort_unless(
            (bool) $tokenManager->getUserFromPlainTextToken($request->api_token),
            Response::HTTP_UNAUTHORIZED
        );

        $url = $iTunesService->getTrackUrl($request->q, $album->name, $album->artist->name);
        abort_unless((bool) $url, Response::HTTP_NOT_FOUND, "Koel can't find such a song on iTunes Store.");

        return redirect($url);
    }
}
