<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\InteractWithMultipleSongsRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class LikeMultipleSongsController extends Controller
{
    /** @param User $user */
    public function __invoke(
        InteractWithMultipleSongsRequest $request,
        InteractionService $interactionService,
        Authenticatable $user
    ) {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::query()->findMany($request->songs);
        $songs->each(fn (Song $song) => $this->authorize('access', $song));

        return response()->json($interactionService->likeMany($songs, $user));
    }
}
