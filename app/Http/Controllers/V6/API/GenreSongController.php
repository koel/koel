<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\V6\API\GenreFetchSongRequest;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Values\Genre;
use Illuminate\Contracts\Auth\Authenticatable;

class GenreSongController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(
        string $genre,
        SongRepository $repository,
        Authenticatable $user,
        GenreFetchSongRequest $request
    ) {
        return SongResource::collection(
            $repository->getByGenre(
                $genre === Genre::NO_GENRE ? '' : $genre,
                $request->sort ?: 'songs.title',
                $request->order ?: 'asc',
                $user
            )
        );
    }
}
