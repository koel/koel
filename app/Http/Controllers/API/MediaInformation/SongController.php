<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Song;
use App\Services\MediaInformationService;
use App\Services\YouTubeService;

class SongController extends Controller
{
    public function __construct(
        protected MediaInformationService $mediaInformationService,
        private YouTubeService $youTubeService
    ) {
        parent::__construct($mediaInformationService);
    }

    public function show(Song $song)
    {
        return response()->json([
            'lyrics' => $song->lyrics,
            'album_info' => $this->mediaInformationService->getAlbumInformation($song->album)?->toArray() ?: [],
            'artist_info' => $this->mediaInformationService->getArtistInformation($song->artist)?->toArray() ?: [],
            'youtube' => $this->youTubeService->searchVideosRelatedToSong($song),
        ]);
    }
}
