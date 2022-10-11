<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Repositories\ArtistRepository;

class ArtistController extends Controller
{
    public function __construct(private ArtistRepository $repository)
    {
    }

    public function index()
    {
        return ArtistResource::collection($this->repository->paginate());
    }

    public function show(Artist $artist)
    {
        return ArtistResource::make($this->repository->getOne($artist->id));
    }
}
