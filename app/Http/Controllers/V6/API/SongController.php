<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\V6\Requests\SongListRequest;
use App\Http\Resources\SongResource;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class SongController extends Controller
{
    /** @param User $user */
    public function __construct(private SongRepository $songRepository, private ?Authenticatable $user)
    {
    }

    public function show(Song $song)
    {
        return SongResource::make($this->songRepository->getOne($song->id));
    }

    public function index(SongListRequest $request)
    {
        return SongResource::collection(
            $this->songRepository->getForListing(
                $request->sort ?: 'songs.title',
                $request->order ?: 'asc',
                $this->user
            )
        );
    }
}
