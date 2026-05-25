<?php

namespace App\Http\Controllers\API\MediaBrowser;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SongFileResource;
use App\Repositories\FolderRepository;
use App\Repositories\SongRepository;
use Illuminate\Support\Arr;

#[RequiresPlus]
class FetchRecursiveFolderSongsController extends Controller
{
    public function __invoke(SongRepository $songRepository, FolderRepository $folderRepository)
    {
        $folders = $folderRepository->getByPublicIds(Arr::wrap(request('folders')));
        $paths = $folders->pluck('path')->all();

        return SongFileResource::collection($songRepository->getUnderPaths($paths));
    }
}
