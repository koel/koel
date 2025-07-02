<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\GenreFetchSongRequest;
use App\Http\Resources\SongResourceCollection;
use App\Models\Genre;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GenreSongController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(
        SongRepository $repository,
        Authenticatable $user,
        GenreFetchSongRequest $request,
    ) {
        /** @var ?Genre $genre */
        $genre = request()->route('genre');

        if ($genre) {
            $songs = $repository->getByGenre(
                $genre,
                $request->sort ? explode(',', $request->sort) : ['songs.title'],
                $request->order ?: 'asc',
                $user
            );
        } else {
            $songs = $repository->getWithNoGenre(
                $request->sort ? explode(',', $request->sort) : ['songs.title'],
                $request->order ?: 'asc',
                $user
            );
        }

        return SongResourceCollection::make($songs);
    }
}
