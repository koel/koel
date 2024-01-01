<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Http\Requests\Download\SongRequest;
use App\Repositories\SongRepository;
use App\Services\DownloadService;

class SongController extends Controller
{
    public function __construct(private DownloadService $downloadService, private SongRepository $songRepository)
    {
    }

    public function show(SongRequest $request)
    {
        $songs = $this->songRepository->getMany($request->songs);

        return response()->download($this->downloadService->from($songs));
    }
}
