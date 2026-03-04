<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Genre\FetchSongsToQueueByGenreRequest;
use App\Http\Resources\SongResource;
use App\Models\Genre;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchSongsToQueueByGenreController extends Controller
{
    /** @param User $user */
    public function __invoke(
        FetchSongsToQueueByGenreRequest $request,
        SongRepository $repository,
        Authenticatable $user,
    ) {
        /** @var ?Genre $genre */
        $genre = request()->route('genre');

        return SongResource::collection($repository->getByGenre($genre, $request->limit, $request->boolean('random')));
    }
}
