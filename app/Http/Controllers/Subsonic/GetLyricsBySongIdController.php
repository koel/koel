<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\SongRepository;

class GetLyricsBySongIdController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $song = $this->songRepository->getOne($request->id);

        $lyrics = trim((string) $song->lyrics);

        if ($lyrics === '') {
            return SubsonicResponse::ok(['lyricsList' => []]);
        }

        $lines = array_map(static fn (string $line) => ['value' => $line], preg_split('/\r\n|\r|\n/', $lyrics) ?: []);

        return SubsonicResponse::ok([
            'lyricsList' => [
                'structuredLyrics' => [
                    [
                        'displayArtist' => $song->artist_name,
                        'displayTitle' => $song->title,
                        'lang' => 'und',
                        'offset' => 0,
                        'synced' => false,
                        'line' => $lines,
                    ],
                ],
            ],
        ]);
    }
}
