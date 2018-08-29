<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\SongRequest;
use App\Repositories\SongRepository;
use App\Services\DownloadService;

class SongController extends Controller
{
    private $songRepository;

    public function __construct(DownloadService $downloadService, SongRepository $songRepository)
    {
        parent::__construct($downloadService);
        $this->songRepository = $songRepository;
    }

    /**
     * Download a song or multiple songs.
     */
    public function show(SongRequest $request)
    {
        $songs = $this->songRepository->getByIds($request->songs);

        return response()->download($this->downloadService->from($songs));
    }
}
