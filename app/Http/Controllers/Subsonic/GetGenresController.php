<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\GenreResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\GenreRepository;
use App\Services\LibrarySummaryService;
use App\Values\GenreSummary;
use Illuminate\Contracts\Auth\Authenticatable;

class GetGenresController extends Controller
{
    public function __construct(
        private readonly LibrarySummaryService $librarySummaryService,
        private readonly GenreRepository $genreRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $albumCounts = $this->genreRepository->getAlbumCountsByGenre($user);

        $genres = $this->librarySummaryService
            ->getGenreSummaries($user)
            ->map(static fn (GenreSummary $genre) => GenreResource::toArray(
                $genre,
                $albumCounts[$genre->publicId] ?? 0,
            ))
            ->all();

        return SubsonicResponse::ok([
            'genres' => [
                'genre' => $genres,
            ],
        ]);
    }
}
