<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class AlbumSongController extends Controller
{
    /** @param User $user */
    public function __construct(private SongRepository $songRepository, private ?Authenticatable $user)
    {
    }

    public function index(Album $album)
    {
        return SongResource::collection($this->songRepository->getByAlbum($album, $this->user));
    }
}
