<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Http\Requests\Download\DownloadSongsRequest;
use App\Models\Song;
use App\Services\DownloadService;

class DownloadSongsController extends Controller
{
    public function __invoke(DownloadSongsRequest $request, DownloadService $service)
    {
        // Don't use SongRepository::findOne() because it'd have been already catered to the current user.
        $song = Song::query()->findOrFail($request->songs[0]);
        $this->authorize('download', $song);

        return $service->getDownloadable($song)?->toResponse();
    }
}
