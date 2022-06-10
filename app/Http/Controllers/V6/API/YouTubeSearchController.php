<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Controllers\V6\Requests\YouTubeSearchRequest;
use App\Models\Song;
use App\Services\YouTubeService;

class YouTubeSearchController extends Controller
{
    public function __invoke(YouTubeSearchRequest $request, Song $song, YouTubeService $youTubeService)
    {
        return $youTubeService->searchVideosRelatedToSong($song, (string) $request->pageToken);
    }
}
