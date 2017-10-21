<?php

namespace App\Http\Controllers\API;

use App\Models\Song;
use Illuminate\Http\JsonResponse;
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
     * @return JsonResponse
     */
    public function searchVideosRelatedToSong(Request $request, Song $song)
    {
        return response()->json($song->getRelatedYouTubeVideos($request->pageToken));
    }
}
