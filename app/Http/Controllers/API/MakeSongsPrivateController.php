<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangeSongsVisibilityRequest;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\SongService;
use Illuminate\Contracts\Auth\Authenticatable;

class MakeSongsPrivateController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ChangeSongsVisibilityRequest $request,
        SongRepository $repository,
        SongService $songService,
        Authenticatable $user
    ) {
        $songs = $repository->getMany(ids: $request->songs, scopedUser: $user);
        $songs->each(fn ($song) => $this->authorize('own', $song));

        $songService->makeSongsPrivate($songs);

        return response()->noContent();
    }
}
