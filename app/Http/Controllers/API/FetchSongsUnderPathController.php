<?php

namespace App\Http\Controllers\API;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SongFileResource;
use App\Repositories\FolderRepository;
use App\Repositories\SongRepository;
use App\Services\MediaBrowser;

#[RequiresPlus]
class FetchSongsUnderPathController extends Controller
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

        return SongFileResource::collection($songRepository->paginateInFolder($folder));
    }
}
