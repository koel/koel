<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetSongsByGenreRequest;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Song;
use App\Models\User;
use App\Repositories\GenreRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetSongsByGenreController extends Controller
{
    public function __construct(
        private readonly GenreRepository $genreRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetSongsByGenreRequest $request, Authenticatable $user)
    {
        $genre = $this->genreRepository->searchByName($request->genre) ?? throw new ModelNotFoundException();

        $songs = $this->songRepository->getByGenre(
            genre: $genre,
            limit: $request->integer('count', 10),
            offset: $request->integer('offset', 0),
            scopedUser: $user,
        );

        return SubsonicResponse::ok([
            'songsByGenre' => [
                'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
            ],
        ]);
    }
}
