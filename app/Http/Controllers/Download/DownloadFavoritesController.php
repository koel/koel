<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Contracts\Auth\Authenticatable;

class DownloadFavoritesController extends Controller
{
    /** @param User $user */
    public function __invoke(DownloadService $download, SongRepository $repository, Authenticatable $user)
    {
        return response()->download($download->from($repository->getFavorites($user)));
    }
}
