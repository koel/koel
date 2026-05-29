<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetLyricsRequest;
use App\Http\Responses\Subsonic\Resources\LyricsResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetLyricsController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetLyricsRequest $request, Authenticatable $user)
    {
        $song = $this->songRepository->findOneByArtistAndTitle($request->artist, $request->title, $user);
        $lyrics = $song ? trim((string) $song->lyrics) : '';

        if ($song === null || $lyrics === '') {
            return SubsonicResponse::ok(['lyrics' => []]);
        }

        return SubsonicResponse::ok(['lyrics' => LyricsResource::toArray($song, $lyrics)]);
    }
}
