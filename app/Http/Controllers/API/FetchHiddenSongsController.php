<?php

namespace App\Http\Controllers\API;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

#[RequiresPlus]
class FetchHiddenSongsController extends Controller
{
    /** @param User $user */
    public function __invoke(SongRepository $repository, Authenticatable $user)
    {
        return SongResource::collection($repository->getHidden($user));
    }
}
