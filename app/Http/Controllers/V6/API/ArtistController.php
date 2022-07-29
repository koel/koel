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
    public function __construct(private ArtistRepository $artistRepository, private ?Authenticatable $user)
    {
    }

    public function index()
    {
        $pagination = Artist::withMeta($this->user)
            ->isStandard()
            ->orderBy('artists.name')
            ->simplePaginate(21);

        return ArtistResource::collection($pagination);
    }

    public function show(Artist $artist)
    {
        return ArtistResource::make($this->artistRepository->getOne($artist->id, $this->user));
    }
}
