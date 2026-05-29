<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\GenreResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\GenreRepository;

class GetGenresController extends Controller
{
    public function __construct(
        private readonly GenreRepository $genreRepository,
    ) {}

    public function __invoke()
    {
        $genres = $this->genreRepository
            ->getAllSummaries()
            ->map(GenreResource::toArray(...))
            ->all();

        return SubsonicResponse::ok([
            'genres' => [
                'genre' => $genres,
            ],
        ]);
    }
}
