<?php

namespace App\Http\Controllers\API;

use App\Enums\Placement;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\MoveFavoriteSongsRequest;
use App\Models\User;
use App\Services\FavoriteService;
use Illuminate\Contracts\Auth\Authenticatable;

class MoveFavoriteSongsController extends Controller
{
    /** @param User $user */
    public function __invoke(MoveFavoriteSongsRequest $request, FavoriteService $service, Authenticatable $user)
    {
        $service->moveFavorites($user, $request->songs, $request->target, Placement::from($request->placement));

        return response()->noContent();
    }
}
