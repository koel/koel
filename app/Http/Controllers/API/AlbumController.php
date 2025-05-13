<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AlbumListRequest;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Repositories\AlbumRepository;

class AlbumController extends Controller
{
    public function __construct(private readonly AlbumRepository $repository)
    {
    }

    public function index(AlbumListRequest $request)
    {
        return AlbumResource::collection($this->repository->getForListing(
            sortColumn: $request->sort ?? 'name',
            sortDirection: $request->order ?? 'asc',
        ));
    }

    public function show(Album $album)
    {
        return AlbumResource::make($album);
    }
}
