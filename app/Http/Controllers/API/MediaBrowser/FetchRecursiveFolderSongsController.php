<?php

namespace App\Http\Controllers\API\MediaBrowser;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SongFileResource;
use App\Repositories\SongRepository;
use Illuminate\Support\Arr;

#[RequiresPlus]
class FetchRecursiveFolderSongsController extends Controller
{
    public function __invoke(SongRepository $repository)
    {
        return SongFileResource::collection($repository->getUnderPaths(paths: Arr::wrap(request('paths'))));
    }
}
