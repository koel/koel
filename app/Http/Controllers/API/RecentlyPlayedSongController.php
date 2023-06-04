<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class RecentlyPlayedSongController extends Controller
{
    private const MAX_ITEM_COUNT = 128;

    /** @param User $user */
    public function __construct(private SongRepository $songRepository, private ?Authenticatable $user)
    {
    }

    public function index()
    {
        return SongResource::collection($this->songRepository->getRecentlyPlayed(self::MAX_ITEM_COUNT, $this->user));
    }
}
