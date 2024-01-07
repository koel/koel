<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Http\Requests\Download\DownloadSongsRequest;
use App\Repositories\SongRepository;
use App\Services\DownloadService;

class DownloadSongsController extends Controller
{
    public function __invoke(DownloadSongsRequest $request, DownloadService $download, SongRepository $repository)
    {
        $songs = $repository->getMany($request->songs);
        $songs->each(fn ($song) => $this->authorize('download', $song));

        return response()->download($download->from($repository->getMany($request->songs)));
    }
}
