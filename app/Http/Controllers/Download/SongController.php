<?php

namespace App\Http\Controllers\Download;

use App\Http\Requests\Download\SongRequest;
use App\Repositories\SongRepository;
use App\Services\DownloadService;

/**
 * @group 6. Download
 */
class SongController extends Controller
{
    private $songRepository;

    public function __construct(DownloadService $downloadService, SongRepository $songRepository)
    {
        parent::__construct($downloadService);
        $this->songRepository = $songRepository;
    }

    /**
     * Download one or several songs
     *
     * @queryParam songs array An array of song IDs
     *
     * @response []
     */
    public function show(SongRequest $request)
    {
        $songs = $this->songRepository->getByIds($request->songs);

        return response()->download($this->downloadService->from($songs));
    }
}
