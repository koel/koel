<?php

namespace App\Http\Controllers\API;

use App\Models\Song;
use Illuminate\Http\Request;
use YouTube;

class YouTubeController extends Controller
{
    /**
     * Search for YouTube videos related to a song (using its title and artist name).
     *
     * @param Request $request
     * @param Song    $song
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchVideosRelatedToSong(Request $request, Song $song)
    {
        return response()->json(YouTube::searchVideosRelatedToSong($song, $request->input('pageToken')));
    }
}
