<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\InteractionRepository;
use App\Services\DownloadService;
use Illuminate\Contracts\Auth\Authenticatable;

class DownloadFavoritesController extends Controller
{
    /** @param User $user */
    public function __invoke(DownloadService $download, InteractionRepository $repository, Authenticatable $user)
    {
        return response()->download($download->from($repository->getUserFavorites($user)));
    }
}
