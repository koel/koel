<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\BatchInteractionRequest;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

class BatchLikeController extends Controller
{
    /** @param User $user */
    public function __construct(
        private SongRepository $songRepository,
        private InteractionService $interactionService,
        private ?Authenticatable $user
    ) {
    }

    public function store(BatchInteractionRequest $request)
    {
        $this->songRepository->getMany(ids: $request->songs, scopedUser: $this->user)
            ->each(fn ($song) => $this->authorize('interact', $song));

        $interactions = $this->interactionService->batchLike(Arr::wrap($request->songs), $this->user);

        return response()->json($interactions);
    }

    public function destroy(BatchInteractionRequest $request)
    {
        $this->songRepository->getMany(ids: $request->songs, scopedUser: $this->user)
            ->each(fn ($song) => $this->authorize('interact', $song));

        $this->interactionService->batchUnlike(Arr::wrap($request->songs), $this->user);

        return response()->noContent();
    }
}
