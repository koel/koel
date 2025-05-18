<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Repositories\SongRepository;
use Illuminate\Support\Arr;

class ResolveSongsByFoldersController extends Controller
{
    public function __invoke(SongRepository $repository)
    {
        return SongResource::collection($repository->getUnderPaths(paths: Arr::wrap(request('paths'))));
    }
}
