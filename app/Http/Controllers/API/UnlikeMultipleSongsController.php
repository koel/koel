<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\InteractWithMultipleSongsRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\FavoriteService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

class UnlikeMultipleSongsController extends Controller
{
    /** @param User $user */
    public function __invoke(
        InteractWithMultipleSongsRequest $request,
        FavoriteService $favoriteService,
        Authenticatable $user
    ) {
        /** @var Collection<int, Song> $songs */
        $songs = Song::query()->findMany($request->songs);
        $songs->each(fn (Song $song) => $this->authorize('access', $song));

        $favoriteService->batchUndoFavorite($songs, $user); // @phpstan-ignore-line

        return response()->noContent();
    }
}
