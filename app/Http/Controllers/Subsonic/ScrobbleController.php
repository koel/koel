<?php

namespace App\Http\Controllers\Subsonic;

use App\Events\PlaybackStarted;
use App\Facades\Dispatcher;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\ScrobbleRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\InteractionService;
use App\Services\ScrobbleService;
use Illuminate\Contracts\Auth\Authenticatable;

class ScrobbleController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly InteractionService $interactionService,
        private readonly ScrobbleService $scrobbleService,
    ) {}

    /** @param User $user */
    public function __invoke(ScrobbleRequest $request, Authenticatable $user)
    {
        $songs = $this->songRepository->getMany($request->id, scopedUser: $user);

        if (!$request->submission) {
            $songs->each(static fn (Song $song) => event(new PlaybackStarted($song, $user)));

            return SubsonicResponse::ok();
        }

        $timestamp = $request->time ? intdiv((int) $request->time, 1000) : now()->getTimestamp();
        $scrobbles = $this->scrobbleService->hasConnectedScrobbler($user);

        foreach ($songs as $song) {
            $this->interactionService->increasePlayCount($song, $user);

            if ($scrobbles && $song->isScrobbleable()) {
                Dispatcher::dispatch(new ScrobbleJob($user, $song, $timestamp));
            }
        }

        return SubsonicResponse::ok();
    }
}
