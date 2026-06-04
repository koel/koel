<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\RateRequest;
use App\Http\Resources\SongResource;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\RatingService;
use Illuminate\Contracts\Auth\Authenticatable;

class RateSongController extends Controller
{
    public function __construct(
        private readonly RatingService $ratingService,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(RateRequest $request, Song $song, Authenticatable $user)
    {
        $this->authorize('access', $song);

        $this->ratingService->setRating($song, $user, $request->rating);

        return SongResource::make($this->songRepository->getOne($song->id, $user))->for($user);
    }
}
