<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ScrobbleRequest;
use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Models\User;
use App\Services\Dispatcher;
use Illuminate\Contracts\Auth\Authenticatable;

class ScrobbleController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ScrobbleRequest $request,
        Song $song,
        Dispatcher $dispatcher,
        Authenticatable $user,
    ) {
        if (!$song->artist->is_unknown && $user->connected_to_lastfm) {
            $dispatcher->dispatch(new ScrobbleJob($user, $song, $request->timestamp));
        }

        return response()->noContent();
    }
}
