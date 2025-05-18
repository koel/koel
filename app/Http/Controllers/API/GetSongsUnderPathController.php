<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Repositories\FolderRepository;
use App\Repositories\SongRepository;
use App\Services\MediaBrowser;

class GetSongsUnderPathController extends Controller
{
    public function __invoke(
        MediaBrowser $browser,
        FolderRepository $folderRepository,
        SongRepository $songRepository,
    ) {
        $folder = $folderRepository->findByPath(request('path'));

        if ($folder) {
            $this->authorize('browse', $folder);
        }

        return SongResource::collection($songRepository->paginateInFolder($folder));
    }
}
