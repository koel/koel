<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use App\Models\User;
use App\Repositories\GenreRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GenreController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly GenreRepository $repository,
        private readonly Authenticatable $user
    ) {
    }

    public function index()
    {
        return GenreResource::collection($this->repository->getAllSummaries($this->user));
    }

    public function show()
    {
        /** @var ?Genre $genre */
        $genre = request()->route('genre');

        $summary = $genre
            ? $this->repository->getSummaryForGenre($genre, $this->user)
            : $this->repository->getSummaryForNoGenre($this->user);

        return GenreResource::make($summary);
    }
}
