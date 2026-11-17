<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\YouTubeSearchRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\Integrations\YouTubeService;
use Illuminate\Contracts\Auth\Authenticatable;

class SearchYouTubeController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly Authenticatable $user,
    ) {}

    public function __invoke(YouTubeSearchRequest $request, Song $song, YouTubeService $youTubeService)
    {
        return response()->json($youTubeService->searchVideosRelatedToSong(
            $song,
            $this->user->organization,
            (string) $request->pageToken,
        ));
    }
}
