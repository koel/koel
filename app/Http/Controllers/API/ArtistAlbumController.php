<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Models\Artist;
use App\Repositories\AlbumRepository;

class ArtistAlbumController extends Controller
{
    public function __construct(private AlbumRepository $albumRepository)
    {
    }

    public function index(Artist $artist)
    {
        return AlbumResource::collection($this->albumRepository->getByArtist($artist));
    }
}
