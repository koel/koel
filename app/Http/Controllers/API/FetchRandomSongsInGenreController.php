<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\FetchRandomSongsInGenreRequest;
use App\Http\Resources\SongResource;
use App\Models\Genre;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchRandomSongsInGenreController extends Controller
{
    /** @param User $user */
    public function __invoke(
        Genre $genre,
        FetchRandomSongsInGenreRequest $request,
        SongRepository $repository,
        Authenticatable $user
    ) {
        return SongResource::collection($repository->getRandomByGenre($genre, $request->limit, $user));
    }
}
