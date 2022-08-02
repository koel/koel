<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\InteractionRepository;
use App\Services\DownloadService;
use Illuminate\Contracts\Auth\Authenticatable;

class FavoritesController extends Controller
{
    /** @param User $user */
    public function __construct(
        private DownloadService $downloadService,
        private InteractionRepository $interactionRepository,
        private ?Authenticatable $user
    ) {
    }

    public function show()
    {
        $songs = $this->interactionRepository->getUserFavorites($this->user);

        return response()->download($this->downloadService->from($songs));
    }
}
