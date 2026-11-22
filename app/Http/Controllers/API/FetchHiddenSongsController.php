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
    public function __construct(
        private readonly SongRepository $repository,
        private readonly Authenticatable $user,
    ) {}

    public function __invoke()
    {
        return SongResource::collection($this->repository->getHidden($this->user));
    }
}
