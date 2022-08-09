<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class AlbumController extends Controller
{
    /** @param User $user */
    public function __construct(private AlbumRepository $repository, private ?Authenticatable $user)
    {
    }

    public function index()
    {
        return AlbumResource::collection($this->repository->paginate($this->user));
    }

    public function show(Album $album)
    {
        return AlbumResource::make($this->repository->getOne($album->id, $this->user));
    }
}
