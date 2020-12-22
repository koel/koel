<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Song;
use App\Services\MediaInformationService;
use App\Services\YouTubeService;

class SongController extends Controller
{
    private $youTubeService;

    public function __construct(MediaInformationService $mediaInformationService, YouTubeService $youTubeService)
    {
        parent::__construct($mediaInformationService);

        $this->youTubeService = $youTubeService;
    }

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
