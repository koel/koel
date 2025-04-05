<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function __construct(private readonly AlbumRepository $repository)
    {
    }

    public function index(Request $request)
    {
        $sortMode = $request->input('sort') ?: 'name';
        return AlbumResource::collection($this->repository->paginate(null, $sortMode));
    }

    public function show(Album $album)
    {
        return AlbumResource::make($album);
    }
}
