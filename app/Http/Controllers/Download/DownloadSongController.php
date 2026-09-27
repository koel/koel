<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Http\Requests\Download\DownloadSongRequest;
use App\Models\Song;
use App\Services\DownloadService;

class DownloadSongController extends Controller
{
    public function __invoke(DownloadSongRequest $request, DownloadService $service)
    {
        // Don't use SongRepository::findOne() because it'd have been already catered to the current user.
        $song = Song::query()->findOrFail($request->songs[0]);
        $this->authorize('download', $song);

        return $service->getDownloadable($song)?->toResponse();
    }
}
