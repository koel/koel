<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Song;
use App\Services\MediaInformationService;
use App\Services\YouTubeService;
use Illuminate\Http\JsonResponse;

/**
 * @group 5. Media information
 */
class SongController extends Controller
{
    private $youTubeService;

    public function __construct(MediaInformationService $mediaInformationService, YouTubeService $youTubeService)
    {
        parent::__construct($mediaInformationService);
        $this->youTubeService = $youTubeService;
    }

    /**
     * Get song's extra information.
     *
     * Get a song's extra information. The response of this request is a superset of both corresponding
     * `album/{album}/info` and `artist/{artist}/info` requests, combined with the song's lyrics and related YouTube
     * videos, if applicable. This means you can (and should) cache this information somewhere ;)
     *
     * @responseFile responses/mediaInformation.song.show.json
     *
     * @return JsonResponse
     */
    public function show(Song $song)
    {
        return response()->json([
            'lyrics' => $song->lyrics,
            'album_info' => $this->mediaInformationService->getAlbumInformation($song->album),
            'artist_info' => $this->mediaInformationService->getArtistInformation($song->artist),
            'youtube' => $this->youTubeService->searchVideosRelatedToSong($song),
        ]);
    }
}
