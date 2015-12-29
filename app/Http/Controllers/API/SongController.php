<?php

namespace App\Http\Controllers\API;

use App\Models\Song;

class SongController extends Controller
{
    /**
     * Get extra information about a song via Last.fm.
     * 
     * @param Song $song
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(Song $song)
    {
        return response()->json([
            'lyrics' => $song->lyrics,
            'album_info' => $song->album->getInfo(),
            'artist_info' => $song->album->artist->getInfo(),
        ]);
    }

    /**
     * Scrobble a song.
     * 
     * @param Song   $song
     * @param string $timestamp The UNIX timestamp when the song started playing.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function scrobble(Song $song, $timestamp)
    {
        return response()->json($song->scrobble($timestamp));
    }
}
