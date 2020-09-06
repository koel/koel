<?php

namespace App\Http\Controllers\API;

use App\Jobs\ScrobbleJob;
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
     * Scrobble a song
     *
     * Create a [Last.fm scrobble entry](https://www.last.fm/api/scrobbling) for a song.
     *
     * @param string $timestamp the UNIX timestamp when the song started playing
     *
     * @return JsonResponse
     */
    public function store(Request $request, Song $song, string $timestamp)
    {
        if (!$song->artist->is_unknown && $request->user()->connectedToLastfm()) {
            ScrobbleJob::dispatch($request->user(), $song, (int) $timestamp);
        }

        return response()->json();
    }
}
