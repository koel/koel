<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FavoriteController extends Controller
{
    /** @param User $user */
    public function __construct(private SongRepository $songRepository, private ?Authenticatable $user)
    {
    }

    public function index()
    {
        return SongResource::collection($this->songRepository->getFavorites($this->user));
    }
}
