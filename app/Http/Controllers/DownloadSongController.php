<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadSongRequest;
use App\Models\Song;
use App\Services\DownloadService;
use Illuminate\Http\Response;

class DownloadSongController extends Controller
{
    public function __invoke(DownloadSongRequest $request, DownloadService $service)
    {
        // Don't use SongRepository::findOne() because it'd have been already catered to the current user.
        $song = Song::query()->findOrFail($request->songs[0]);
        $this->authorize('download', $song);

        $downloadable = $service->getDownloadable($song);
        abort_unless((bool) $downloadable, Response::HTTP_NOT_FOUND);

        return $downloadable->toResponse();
    }
}
