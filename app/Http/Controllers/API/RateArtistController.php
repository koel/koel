<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\RateRequest;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Services\RatingService;
use Illuminate\Contracts\Auth\Authenticatable;

class RateArtistController extends Controller
{
    public function __construct(
        private readonly RatingService $ratingService,
        private readonly ArtistRepository $artistRepository,
    ) {}

    /** @param User $user */
    public function __invoke(RateRequest $request, Artist $artist, Authenticatable $user)
    {
        $this->authorize('access', $artist);

        $this->ratingService->setRating($artist, $user, $request->rating);

        return ArtistResource::make($this->artistRepository->getOne($artist->id, $user))->for($user);
    }
}
