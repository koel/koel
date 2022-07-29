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
    public function __construct(private AlbumRepository $albumRepository, private ?Authenticatable $user)
    {
    }

    public function index()
    {
        $pagination = Album::withMeta($this->user)
            ->isStandard()
            ->orderBy('albums.name')
            ->simplePaginate(21);

        return AlbumResource::collection($pagination);
    }

    public function show(Album $album)
    {
        return AlbumResource::make($this->albumRepository->getOne($album->id, $this->user));
    }
}
