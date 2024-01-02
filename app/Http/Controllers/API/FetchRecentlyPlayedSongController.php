<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchRecentlyPlayedSongController extends Controller
{
    private const MAX_ITEM_COUNT = 128;

    /** @param User $user */
    public function __invoke(SongRepository $repository, ?Authenticatable $user)
    {
        return SongResource::collection($repository->getRecentlyPlayed(self::MAX_ITEM_COUNT, $user));
    }
}
