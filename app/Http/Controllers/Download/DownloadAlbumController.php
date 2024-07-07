<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Contracts\Auth\Authenticatable;

class DownloadAlbumController extends Controller
{
    /** @param User $user */
    public function __invoke(
        Album $album,
        SongRepository $repository,
        DownloadService $service,
        Authenticatable $user
    ) {
        return response()->download($service->getDownloadablePath($repository->getByAlbum($album, $user)));
    }
}
