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
        $q = $song->title;

        // If the artist is worth noticing, include them into the search.
        if (!$song->artist->isUnknown() && !$song->artist->isVarious()) {
            $q .= ' '.$song->artist->name;
        }

        return response()->json(YouTube::search($q, $request->input('pageToken')));
    }
}
