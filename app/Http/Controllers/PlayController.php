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
    /**
     * @param User $user
     * @param ?bool $transcode Whether to **force** transcoding (on mobile devices).
     */
    public function __invoke(Authenticatable $user, SongPlayRequest $request, Song $song, ?bool $transcode = null)
    {
        $this->authorize('access', $song);

        // If force transcoding is enabled, use the user's transcoding quality preference.
        // Otherwise, set this value to null. If the file does indeed need transcoding,
        // the Streamer will use the config value from .env.
        $transcodeBitRate = $transcode
            ? (int) filter_var($user->preferences->transcodeQuality, FILTER_SANITIZE_NUMBER_INT)
            : null;

        return (new Streamer(
            song: $song,
            config: RequestedStreamingConfig::make(
                transcode: (bool) $transcode,
                bitRate: $transcodeBitRate,
                startTime: (float) $request->time
            )
        ))->stream();
    }
}
