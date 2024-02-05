<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Http\Requests\Download\DownloadSongsRequest;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Http\Response;

class DownloadSongsController extends Controller
{
    public function __invoke(DownloadSongsRequest $request, DownloadService $service, SongRepository $repository)
    {
        // Don't use SongRepository::findMany() because it'd have been already catered to the current user.
        $songs = Song::query()->findMany($request->songs);
        $songs->each(fn ($song) => $this->authorize('download', $song));

        $downloadablePath = $service->getDownloadablePath($repository->getMany($request->songs));

        abort_unless((bool) $downloadablePath, Response::HTTP_BAD_REQUEST, 'Song cannot be downloaded.');

        return response()->download($downloadablePath);
    }
}
