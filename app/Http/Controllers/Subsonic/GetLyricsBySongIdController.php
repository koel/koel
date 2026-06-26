<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\StructuredLyricsResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\SongRepository;
use App\Values\ParsedLyrics;

class GetLyricsBySongIdController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $song = $this->songRepository->getOne($request->id);
        $lyrics = ParsedLyrics::fromRawLyrics((string) $song->lyrics);

        if (!$lyrics->lines) {
            return SubsonicResponse::ok(['lyricsList' => []]);
        }

        return SubsonicResponse::ok([
            'lyricsList' => [
                'structuredLyrics' => [
                    StructuredLyricsResource::toArray($song, $lyrics),
                ],
            ],
        ]);
    }
}
