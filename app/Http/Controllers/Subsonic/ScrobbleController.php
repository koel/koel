<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\ScrobbleRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class ScrobbleController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly InteractionService $interactionService,
    ) {}

    /** @param User $user */
    public function __invoke(ScrobbleRequest $request, Authenticatable $user)
    {
        if ($request->submission) {
            foreach ($this->songRepository->getMany($request->id, scopedUser: $user) as $song) {
                $this->interactionService->increasePlayCount($song, $user);
            }
        }

        return SubsonicResponse::ok();
    }
}
