<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class ArtistController extends Controller
{
    /** @param User $user */
    public function __construct(private ArtistRepository $repository, private ?Authenticatable $user)
    {
    }

    public function index()
    {
        return ArtistResource::collection($this->repository->paginate($this->user));
    }

    public function show(Artist $artist)
    {
        return ArtistResource::make($this->repository->getOne($artist->id, $this->user));
    }
}
