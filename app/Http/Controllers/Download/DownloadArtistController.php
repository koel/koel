<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Contracts\Auth\Authenticatable;

class DownloadArtistController extends Controller
{
    /** @param User $user */
    public function __invoke(
        Artist $artist,
        SongRepository $repository,
        DownloadService $service,
        Authenticatable $user
    ) {
        return response()->download($service->getDownloadablePath($repository->getByArtist($artist, $user)));
    }
}
