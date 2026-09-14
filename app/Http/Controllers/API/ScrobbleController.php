<?php

namespace App\Http\Controllers\API;

use App\Facades\Dispatcher;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ScrobbleRequest;
use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Models\User;
use App\Services\ScrobbleService;
use Illuminate\Contracts\Auth\Authenticatable;

class ScrobbleController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ScrobbleRequest $request,
        ScrobbleService $scrobbleService,
        Song $song,
        Authenticatable $user,
    ) {
        if (!$song->artist->is_unknown && $scrobbleService->hasConnectedScrobbler($user)) {
            Dispatcher::dispatch(new ScrobbleJob($user, $song, $request->timestamp));
        }

        return response()->noContent();
    }
}
