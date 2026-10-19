<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetRandomSongsRequest;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Song;
use App\Models\User;
use App\Repositories\GenreRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetRandomSongsController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly GenreRepository $genreRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetRandomSongsRequest $request, Authenticatable $user)
    {
        $size = $request->integer('size', 10);
        $fromYear = $request->filled('fromYear') ? $request->integer('fromYear') : null;
        $toYear = $request->filled('toYear') ? $request->integer('toYear') : null;

        if (!$request->genre && $fromYear === null && $toYear === null) {
            $songs = $this->songRepository->getRandom($size, $user);
        } else {
            $genre = $request->genre ? $this->genreRepository->findOneBy(['name' => $request->genre]) : null;

            $songs = $request->genre && !$genre
                ? collect()
                : $this->songRepository->getRandomInGenreAndYears($size, $genre, $fromYear, $toYear, $user);
        }

        return SubsonicResponse::ok([
            'randomSongs' => [
                'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
            ],
        ]);
    }
}
