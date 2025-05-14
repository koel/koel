<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ArtistListRequest;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Repositories\ArtistRepository;

class ArtistController extends Controller
{
    public function __construct(private readonly ArtistRepository $repository)
    {
    }

    public function index(ArtistListRequest $request)
    {
        return ArtistResource::collection($this->repository->getForListing(
            sortColumn: $request->sort ?? 'name',
            sortDirection: $request->order ?? 'asc',
        ));
    }

    public function show(Artist $artist)
    {
        return ArtistResource::make($artist);
    }
}
