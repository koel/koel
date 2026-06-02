<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Repositories\AlbumRepository;

class FetchRandomAlbumsController extends Controller
{
    public function __invoke(AlbumRepository $albumRepository)
    {
        return AlbumResource::collection($albumRepository->getRandom(15));
    }
}
