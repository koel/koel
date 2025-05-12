<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongPlayRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\Streamer\Streamer;
use App\Values\RequestedStreamingConfig;
use Illuminate\Contracts\Auth\Authenticatable;

class PlayController extends Controller
{
    /** @param User $user */
    public function __invoke(Authenticatable $user, SongPlayRequest $request, Song $song, ?bool $transcode = null)
    {
        $this->authorize('access', $song);

        return (new Streamer(
            song: $song,
            config: RequestedStreamingConfig::make(
                transcode: (bool) $transcode,
                bitRate: (int) filter_var($user->preferences->transcodeQuality, FILTER_SANITIZE_NUMBER_INT),
                startTime: (float) $request->time
            )
        ))->stream();
    }
}
