<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FavoriteSongController extends Controller
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
