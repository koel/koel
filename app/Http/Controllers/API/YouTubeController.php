<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\YouTubeSearchRequest;
use App\Models\Song;
use App\Services\YouTubeService;

class YouTubeController extends Controller
{
    private $youTubeService;

    public function __construct(YouTubeService $youTubeService)
    {
        $this->youTubeService = $youTubeService;
    }

    public function searchVideosRelatedToSong(YouTubeSearchRequest $request, Song $song)
    {
        return response()->json($this->youTubeService->searchVideosRelatedToSong($song, $request->pageToken));
    }
}
