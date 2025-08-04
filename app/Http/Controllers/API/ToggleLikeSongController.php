<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ToggleLikeSongRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Song;
use App\Models\User;
use App\Services\FavoriteService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @deprecated @see FavoriteController instead.
 */
class ToggleLikeSongController extends Controller
{
    /** @param User $user */
    public function __invoke(ToggleLikeSongRequest $request, FavoriteService $service, Authenticatable $user)
    {
        /** @var Song $song */
        $song = Song::query()->findOrFail($request->song);
        $this->authorize('access', $song);

        return FavoriteResource::make($service->toggleFavorite($song, $user));
    }
}
