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
     * @param Request $request
     * @param Song    $song
     * @param string  $timestamp The UNIX timestamp when the song started playing.
     *
     * @return JsonResponse
     */
    public function store(Request $request, Song $song, $timestamp)
    {
        if ($song->artist->is_unknown) {
            return response()->json();
        }

        if (!$request->user()->connectedToLastfm()) {
            return response()->json();
        }

        return response()->json($this->lastfmService->scrobble(
            $song->artist->name,
            $song->title,
            $timestamp,
            $song->album->name === Album::UNKNOWN_NAME ? '' : $song->album->name,
            $request->user()->lastfm_session_key
        ));
    }
}
