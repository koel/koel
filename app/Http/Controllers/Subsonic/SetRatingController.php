<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\SetRatingRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Services\RatingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SetRatingController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly RatingService $ratingService,
    ) {}

    /** @param User $user */
    public function __invoke(SetRatingRequest $request, Authenticatable $user)
    {
        $entity =
            $this->songRepository->findOne($request->id, $user) ?? $this->albumRepository->findOne(
                $request->id,
                $user,
            ) ?? $this->artistRepository->findOne($request->id, $user) ?? throw new ModelNotFoundException();

        $this->ratingService->setRating($entity, $user, $request->rating);

        return SubsonicResponse::ok();
    }
}
