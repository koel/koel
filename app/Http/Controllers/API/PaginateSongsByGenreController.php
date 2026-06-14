<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Genre\PaginateSongsByGenreRequest;
use App\Http\Resources\SongResource;
use App\Models\Genre;
use App\Models\User;
use App\Repositories\Pagination\PaginationStrategyResolver;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class PaginateSongsByGenreController extends Controller
{
    /** @param User $user */
    public function __invoke(PaginateSongsByGenreRequest $request, SongRepository $repository, Authenticatable $user)
    {
        /** @var ?Genre $genre */
        $genre = request()->route('genre');

        return SongResource::collection($repository->paginateByGenre(
            genre: $genre,
            sortColumns: $request->sort ? explode(',', $request->sort) : ['songs.title'],
            sortDirection: $request->order ?: 'asc',
            strategy: PaginationStrategyResolver::resolve($request),
            scopedUser: $user,
        ));
    }
}
