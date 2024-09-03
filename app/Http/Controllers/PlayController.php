<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongPlayRequest;
use App\Models\Song;
use App\Models\User;
use App\Services\Streamer\Streamer;
use Illuminate\Contracts\Auth\Authenticatable;

class PlayController extends Controller
{
    /** @param User $user */
    public function __invoke(Authenticatable $user, SongPlayRequest $request, Song $song, ?bool $transcode = null)
    {
        $this->authorize('access', $song);

        return (new Streamer(song: $song, config: [
            'transcode' => (bool) $transcode,
            'bit_rate' => $user->preferences->transcodeQuality,
            'start_time' => (float) $request->time,
        ]))->stream();
    }
}
