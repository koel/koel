<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class ArtistSongController extends Controller
{
    /** @param User $user */
    public function __construct(private SongRepository $songRepository, private ?Authenticatable $user)
    {
    }

    public function index(Artist $artist)
    {
        return SongResource::collection($this->songRepository->getByArtist($artist, $this->user));
    }
}
