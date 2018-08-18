<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Song;
use Illuminate\Http\JsonResponse;

class SongController extends Controller
{
    /**
     * Get extra information about a song.
     *
     * @param Song $song
     *
     * @return JsonResponse
     */
    public function show(Song $song)
    {
        return response()->json([
            'lyrics' => $song->lyrics,
            'album_info' => $this->mediaInformationService->getAlbumInformation($song->album),
            'artist_info' => $this->mediaInformationService->getArtistInformation($song->artist),
            'youtube' => $song->getRelatedYouTubeVideos(),
        ]);
    }
}
