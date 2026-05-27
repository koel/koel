<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\SongRepository;
use App\Services\Streamer\Streamer;
use App\Values\RequestedStreamingConfig;

class StreamController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $song = $this->songRepository->getOne($request->id);
        $this->authorize('access', $song);

        $maxBitRate = $request->integer('maxBitRate') ?: null;
        $startTime = (float) $request->input('timeOffset', 0);

        return (new Streamer(song: $song, config: RequestedStreamingConfig::make(
            transcode: $maxBitRate !== null,
            bitRate: $maxBitRate,
            startTime: $startTime,
        )))->stream();
    }
}
