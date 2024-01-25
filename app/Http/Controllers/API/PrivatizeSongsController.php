<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangeSongsVisibilityRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\SongService;
use Illuminate\Contracts\Auth\Authenticatable;

class PrivatizeSongsController extends Controller
{
    /** @param User $user */
    public function __invoke(ChangeSongsVisibilityRequest $request, SongService $songService, Authenticatable $user)
    {
        $songs = Song::query()->findMany($request->songs);
        $songs->each(fn ($song) => $this->authorize('own', $song));

        return response()->json($songService->markSongsAsPrivate($songs));
    }
}
