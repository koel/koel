<?php

namespace App\Http\Controllers\API\Podcast;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\Song as Episode;

class FetchEpisodeController extends Controller
{
    public function __invoke(Episode $episode)
    {
        $this->authorize('view', $episode->podcast);

        return SongResource::make($episode);
    }
}
