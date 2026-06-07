<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\RateRequest;
use App\Http\Resources\PodcastResource;
use App\Models\Podcast;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Services\RatingService;
use Illuminate\Contracts\Auth\Authenticatable;

class RatePodcastController extends Controller
{
    public function __construct(
        private readonly RatingService $ratingService,
        private readonly PodcastRepository $podcastRepository,
    ) {}

    /** @param User $user */
    public function __invoke(RateRequest $request, Podcast $podcast, Authenticatable $user)
    {
        $this->authorize('access', $podcast);

        $this->ratingService->setRating($podcast, $user, $request->rating);

        return PodcastResource::make($this->podcastRepository->getOne($podcast->id, $user));
    }
}
