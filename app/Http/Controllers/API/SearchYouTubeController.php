<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\YouTubeSearchRequest;
use App\Models\Song;
use App\Services\YouTubeService;

class SearchYouTubeController extends Controller
{
    public function __invoke(YouTubeSearchRequest $request, Song $song, YouTubeService $youTubeService)
    {
        return response()->json($youTubeService->searchVideosRelatedToSong($song, $request->pageToken));
    }
}
