<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\YouTubeSearchRequest;
use App\Models\Song;
use Illuminate\Http\JsonResponse;

class YouTubeController extends Controller
{
    /**
     * Search for YouTube videos related to a song (using its title and artist name).
     *
     * @param YouTubeSearchRequest $request
     * @param Song                 $song
     *
     * @return JsonResponse
     */
    public function searchVideosRelatedToSong(YouTubeSearchRequest $request, Song $song)
    {
        return response()->json($song->getRelatedYouTubeVideos($request->pageToken));
    }
}
