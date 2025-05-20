<?php

namespace App\Http\Controllers\API;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SongFileResource;
use App\Repositories\FolderRepository;
use App\Repositories\SongRepository;

#[RequiresPlus]
class FetchSongsInPathController extends Controller
{
    public function __invoke(SongRepository $songRepository, FolderRepository $folderRepository)
    {
        $folder = $folderRepository->findByPath(request('path'));

        if ($folder) {
            $this->authorize('browse', $folder);
        }

        return SongFileResource::collection($songRepository->getInFolder($folder));
    }
}
