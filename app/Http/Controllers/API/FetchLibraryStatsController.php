<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryStatResource;
use App\Models\User;
use App\Services\LibraryStatsService;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchLibraryStatsController extends Controller
{
    public function __construct(
        private readonly LibraryStatsService $libraryStatsService,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        return LibraryStatResource::collection($this->libraryStatsService->getStats($user));
    }
}
