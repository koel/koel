<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\QueueFetchSongRequest;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class QueueController extends Controller
{
    /** @param User $user */
    public function __construct(private SongRepository $songRepository, private ?Authenticatable $user)
    {
    }

    public function fetchSongs(QueueFetchSongRequest $request)
    {
        if ($request->order === 'rand') {
            return SongResource::collection($this->songRepository->getRandom($request->limit, $this->user));
        }

        return SongResource::collection(
            $this->songRepository->getForQueue(
                $request->sort,
                $request->order,
                $request->limit,
                $this->user
            )
        );
    }
}
