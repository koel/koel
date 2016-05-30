<?php

namespace App\Http\Controllers\API;

use App\Models\Song;

class ScrobbleController extends Controller
{
    /**
     * Create a Last.fm scrobble entry for a song.
     *
     * @param Song   $song
     * @param string $timestamp The UNIX timestamp when the song started playing.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Song $song, $timestamp)
    {
        return response()->json($song->scrobble($timestamp));
    }
}
