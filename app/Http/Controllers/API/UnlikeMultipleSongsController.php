<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\InteractWithMultipleSongsRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class UnlikeMultipleSongsController extends Controller
{
    /** @param User $user */
    public function __invoke(
        InteractWithMultipleSongsRequest $request,
        InteractionService $interactionService,
        Authenticatable $user
    ) {
        $songs = Song::query()->findMany($request->songs);
        $songs->each(fn (Song $song) => $this->authorize('access', $song));

        $interactionService->unlikeMany($songs, $user);

        return response()->noContent();
    }
}
