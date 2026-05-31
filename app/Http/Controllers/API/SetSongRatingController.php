<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SetSongRatingRequest;
use App\Http\Resources\SongResource;
use App\Models\Song;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Contracts\Auth\Authenticatable;

class SetSongRatingController extends Controller
{
    public function __construct(
        private readonly RatingService $ratingService,
    ) {}

    /** @param User $user */
    public function __invoke(SetSongRatingRequest $request, Song $song, Authenticatable $user)
    {
        $this->authorize('access', $song);

        $this->ratingService->setRating($song, $user, $request->rating);

        return SongResource::make($song->refresh())->for($user);
    }
}
