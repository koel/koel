<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Repositories\InteractionRepository;
use App\Services\DownloadService;

class FavoritesController extends Controller
{
    private $interactionRepository;

    public function __construct(DownloadService $downloadService, InteractionRepository $interactionRepository)
    {
        parent::__construct($downloadService);
        $this->interactionRepository = $interactionRepository;
    }

    /**
     * Download all songs favorite'd by the current user.
     */
    public function show(Request $request)
    {
        $songs = $this->interactionRepository->getUserFavorites($request->user());

        return response()->download($this->downloadService->from($songs));
    }
}
