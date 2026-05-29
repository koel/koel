<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetLyricsRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\SongRepository;

class GetLyricsController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    public function __invoke(GetLyricsRequest $request)
    {
        $song = $this->songRepository->findOneByArtistAndTitle($request->artist, $request->title);
        $lyrics = $song ? trim((string) $song->lyrics) : '';

        if ($song === null || $lyrics === '') {
            return SubsonicResponse::ok(['lyrics' => []]);
        }

        return SubsonicResponse::ok([
            'lyrics' => [
                'artist' => $song->artist_name,
                'title' => $song->title,
                'value' => $lyrics,
            ],
        ]);
    }
}
