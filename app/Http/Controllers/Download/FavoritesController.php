<?php

namespace App\Http\Controllers\Download;

use App\Http\Requests\Download\Request;
use App\Repositories\InteractionRepository;
use App\Services\DownloadService;

class FavoritesController extends Controller
{
    private InteractionRepository $interactionRepository;

    public function __construct(DownloadService $downloadService, InteractionRepository $interactionRepository)
    {
        parent::__construct($downloadService);

        $this->interactionRepository = $interactionRepository;
    }

    public function show(Request $request)
    {
        $songs = $this->interactionRepository->getUserFavorites($request->user());

        return response()->download($this->downloadService->from($songs));
    }
}
