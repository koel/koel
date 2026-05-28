<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetRandomSongsRequest;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetRandomSongsController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetRandomSongsRequest $request, Authenticatable $user)
    {
        $songs = $this->songRepository->getRandom($request->integer('size', 10), $user);

        return SubsonicResponse::ok([
            'randomSongs' => [
                'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
            ],
        ]);
    }
}
