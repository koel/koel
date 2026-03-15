<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FetchSongsByIdsController extends Controller
{
    /** @param User $user */
    public function __invoke(
        Request $request,
        SongRepository $songRepository,
        Authenticatable $user,
    ): ResourceCollection {
        $ids = $request->input('ids', []);

        return SongResource::collection($songRepository->getMany($ids, scopedUser: $user));
    }
}
