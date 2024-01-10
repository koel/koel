<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangeSongsVisibilityRequest;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\SongService;
use Illuminate\Contracts\Auth\Authenticatable;

class MakeSongsPublicController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ChangeSongsVisibilityRequest $request,
        SongRepository $repository,
        SongService $songService,
        Authenticatable $user
    ) {
        $songs = Song::query()->find($request->songs);
        $songs->each(fn ($song) => $this->authorize('own', $song));

        $songService->makeSongsPublic($songs);

        return response()->noContent();
    }
}
