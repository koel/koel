<?php

namespace App\Http\Controllers\API;

use App\Models\Album;
use App\Models\Song;
use App\Services\LastfmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScrobbleController extends Controller
{
    private $lastfmService;

    public function __construct(LastfmService $lastfmService)
    {
        $this->lastfmService = $lastfmService;
    }

    /**
     * Create a Last.fm scrobble entry for a song.
     *
     * @param string $timestamp The UNIX timestamp when the song started playing.
     *
     * @return JsonResponse
     */
    public function store(Request $request, Song $song, string $timestamp)
    {
        if (!$song->artist->is_unknown && $this->lastfmService->isUserConnected($request->user())) {
            $this->lastfmService->scrobble(
                $song->artist->name,
                $song->title,
                (int) $timestamp,
                $song->album->name === Album::UNKNOWN_NAME ? '' : $song->album->name,
                $this->lastfmService->getUserSessionKey($request->user())
            );
        }

        return response()->json();
    }
}
