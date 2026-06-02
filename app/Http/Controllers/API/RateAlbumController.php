<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\RateRequest;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Services\RatingService;
use Illuminate\Contracts\Auth\Authenticatable;

class RateAlbumController extends Controller
{
    public function __construct(
        private readonly RatingService $ratingService,
        private readonly AlbumRepository $albumRepository,
    ) {}

    /** @param User $user */
    public function __invoke(RateRequest $request, Album $album, Authenticatable $user)
    {
        $this->authorize('access', $album);

        $this->ratingService->setRating($album, $user, $request->rating);

        return AlbumResource::make($this->albumRepository->getOne($album->id, $user))->for($user);
    }
}
