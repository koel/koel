<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\YouTubeSearchRequest;
use App\Models\Song;
use App\Services\YouTubeService;

class YouTubeController extends Controller
{
    public function __construct(private YouTubeService $youTubeService)
    {
    }

    public function searchVideosRelatedToSong(YouTubeSearchRequest $request, Song $song)
    {
        return response()->json($this->youTubeService->searchVideosRelatedToSong($song, $request->pageToken));
    }
}
