<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\SongRepository;
use App\Services\Streamer\Streamer;
use App\Values\RequestedStreamingConfig;

class DownloadController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $song = $this->songRepository->getOne($request->id);
        $this->authorize('access', $song);

        return (new Streamer(song: $song, config: RequestedStreamingConfig::make(
            transcode: false,
            bitRate: null,
        )))->stream();
    }
}
