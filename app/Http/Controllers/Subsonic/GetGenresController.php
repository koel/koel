<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\GenreResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\LibrarySummaryService;
use Illuminate\Contracts\Auth\Authenticatable;

class GetGenresController extends Controller
{
    public function __construct(
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $genres = $this->librarySummaryService
            ->getGenreSummaries($user)
            ->map(GenreResource::toArray(...))
            ->all();

        return SubsonicResponse::ok([
            'genres' => [
                'genre' => $genres,
            ],
        ]);
    }
}
