<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\FavoriteRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\FavoriteService;
use App\Services\Subsonic\FavoriteTargetResolver;
use Illuminate\Contracts\Auth\Authenticatable;

class UnstarController extends Controller
{
    public function __construct(
        private readonly FavoriteTargetResolver $resolver,
        private readonly FavoriteService $favoriteService,
    ) {}

    /** @param User $user */
    public function __invoke(FavoriteRequest $request, Authenticatable $user)
    {
        $targets = $this->resolver->resolveForUnfavorite($request, $user);

        if ($targets->isNotEmpty()) {
            $this->favoriteService->batchUndoFavorite($targets, $user);
        }

        return SubsonicResponse::ok();
    }
}
