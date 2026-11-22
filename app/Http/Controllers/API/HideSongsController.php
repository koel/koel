<?php

namespace App\Http\Controllers\API;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangeSongsHiddenStatusRequest;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\HiddenSongService;
use Illuminate\Contracts\Auth\Authenticatable;

#[RequiresPlus]
class HideSongsController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly HiddenSongService $service,
        private readonly SongRepository $songRepository,
        private readonly Authenticatable $user,
    ) {}

    public function __invoke(ChangeSongsHiddenStatusRequest $request)
    {
        $songs = $this->songRepository->getManyRegardlessOfAccess($request->songs);
        $songs->each(fn (Song $song) => $this->authorize('access', $song));

        $this->service->hideSongs($songs, $this->user);

        return response()->noContent();
    }
}
