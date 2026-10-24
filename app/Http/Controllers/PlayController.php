<?php

namespace App\Http\Controllers;

use App\Enums\NativelyPlayableLosslessFormat;
use App\Http\Requests\SongPlayRequest;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\Streamer\Streamer;
use App\Values\RequestedStreamingConfig;
use Illuminate\Contracts\Auth\Authenticatable;

class PlayController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    /**
     * @param User $user
     * @param ?bool $transcode Whether to **force** transcoding (on mobile devices).
     */
    public function __invoke(Authenticatable $user, SongPlayRequest $request, string $songId, ?bool $transcode = null)
    {
        $song = $this->songRepository->getOneWithoutRelations($songId);

        if ($song->isEpisode()) {
            $song->load('podcast');
        }

        $this->authorize('access', $song);

        // If force transcoding is enabled, use the user's transcoding quality preference.
        // Otherwise, set this value to null. If the file does indeed need transcoding,
        // the Streamer will use the config value from .env.
        $transcodeBitRate = $transcode
            ? (int) filter_var($user->preferences->transcodeQuality, FILTER_SANITIZE_NUMBER_INT)
            : null;

        return (new Streamer(song: $song, config: RequestedStreamingConfig::make(
            transcode: (bool) $transcode,
            bitRate: $transcodeBitRate,
            startTime: (float) $request->time,
            clientLosslessFormats: $request->enums('lossless', NativelyPlayableLosslessFormat::class),
        )))->stream();
    }
}
