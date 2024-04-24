<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ToggleLikeSongRequest;
use App\Http\Resources\InteractionResource;
use App\Models\Song;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class ToggleLikeSongController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ToggleLikeSongRequest $request,
        InteractionService $interactionService,
        ?Authenticatable $user
    ) {
        /** @var Song $song */
        $song = Song::query()->findOrFail($request->song);
        $this->authorize('access', $song);

        return InteractionResource::make($interactionService->toggleLike($song, $user));
    }
}
