<?php

namespace App\Http\Controllers\API;

use App\Models\Album;
use App\Models\Song;
use App\Services\LastfmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Last.fm integration
 */
class ScrobbleController extends Controller
{
    private $lastfmService;

    public function __construct(LastfmService $lastfmService)
    {
        $this->lastfmService = $lastfmService;
    }

    /**
     * Scrobble a song.
     *
     * Create a [Last.fm scrobble entry](https://www.last.fm/api/scrobbling) for a song.
     *
     * @param string $timestamp The UNIX timestamp when the song started playing.
     *
     * @return JsonResponse
     */
    public function store(Request $request, Song $song, string $timestamp)
    {
        if (!$song->artist->is_unknown && $request->user()->connectedToLastfm()) {
            $this->lastfmService->scrobble(
                $song->artist->name,
                $song->title,
                (int) $timestamp,
                $song->album->name === Album::UNKNOWN_NAME ? '' : $song->album->name,
                $request->user()->lastfm_session_key
            );
        }

        return response()->json();
    }
}
