<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\V6\API\FetchRandomSongsInGenreRequest;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchRandomSongsInGenreController extends Controller
{
    /** @param User $user */
    public function __invoke(
        FetchRandomSongsInGenreRequest $request,
        SongRepository $repository,
        Authenticatable $user
    ) {
        return SongResource::collection($repository->getRandomByGenre($request->genre, $request->limit, $user));
    }
}
