<?php

namespace App\Http\Controllers\API;

use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScrobbleController extends Controller
{
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
        return response()->json($song->scrobble($request->user(), $timestamp));
    }
}
