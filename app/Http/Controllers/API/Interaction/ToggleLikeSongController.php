<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ToggleLikeSongRequest;
use App\Http\Resources\InteractionResource;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class ToggleLikeSongController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ToggleLikeSongRequest $request,
        SongRepository $songRepository,
        InteractionService $interactionService,
        ?Authenticatable $user
    ) {
        $song = $songRepository->getOne($request->song, $user);
        $this->authorize('interact', $song);

        return InteractionResource::make($interactionService->toggleLike($request->song, $user));
    }
}
