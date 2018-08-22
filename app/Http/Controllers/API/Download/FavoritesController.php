<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Models\Song;
use App\Services\DownloadService;
use App\Services\InteractionService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FavoritesController extends Controller
{
    private $interactionService;

    public function __construct(DownloadService $downloadService, InteractionService $interactionService)
    {
        parent::__construct($downloadService);
        $this->interactionService = $interactionService;
    }

    /**
     * Download all songs in a playlist.
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function show(Request $request)
    {
        $songs = $this->interactionService->getUserFavorites($request->user());

        return response()->download($this->downloadService->from($songs));
    }
}
