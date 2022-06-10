<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Controllers\V6\Requests\QueueFetchSongRequest;
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
        } else {
            return SongResource::collection(
                $this->songRepository->getForQueue(
                    $request->sort,
                    $request->order,
                    $this->user,
                    $request->limit,
                )
            );
        }
    }
}
