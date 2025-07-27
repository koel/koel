<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\FetchSongsForQueueRequest;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchSongsForQueueController extends Controller
{
    /** @param User $user */
    public function __invoke(FetchSongsForQueueRequest $request, SongRepository $repository, Authenticatable $user)
    {
        return SongResource::collection(
            $request->order === 'rand'
                ? $repository->getRandom($request->limit, $user)
                : $repository->getForQueue(
                    explode(',', $request->sort),
                    $request->order,
                    $request->limit,
                    $user
                )
        );
    }
}
