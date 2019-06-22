<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\YouTubeSearchRequest;
use App\Models\Song;
use App\Services\YouTubeService;
use Illuminate\Http\JsonResponse;

/**
 * @group YouTube integration
 */
class YouTubeController extends Controller
{
    private $youTubeService;

    public function __construct(YouTubeService $youTubeService)
    {
        $this->youTubeService = $youTubeService;
    }

    /**
     * Search for YouTube videos.
     *
     * Search YouTube for videos related to a song (using its title and artist name).
     *
     * @bodyParam pageToken string The [`nextPageToken`](https://developers.google.com/youtube/v3/guides/implementation/pagination), if applicable.
     * @responseFile responses/youTube.searchVideosRelatedToSong.json
     *
     * @return JsonResponse
     */
    public function searchVideosRelatedToSong(YouTubeSearchRequest $request, Song $song)
    {
        return response()->json($this->youTubeService->searchVideosRelatedToSong($song, $request->pageToken));
    }
}
