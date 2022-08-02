<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Services\MediaInformationService;
use App\Services\YouTubeService;

class SongController extends Controller
{
    public function __construct(
        private MediaInformationService $mediaInformationService,
        private YouTubeService $youTubeService
    ) {
    }

    public function show(Song $song)
    {
        return response()->json([
            'lyrics' => nl2br($song->lyrics), // backward compat
            'album_info' => $this->mediaInformationService->getAlbumInformation($song->album)?->toArray() ?: [],
            'artist_info' => $this->mediaInformationService->getArtistInformation($song->artist)?->toArray() ?: [],
            'youtube' => $this->youTubeService->searchVideosRelatedToSong($song),
        ]);
    }
}
