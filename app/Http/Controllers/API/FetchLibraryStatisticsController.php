<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryStatisticResource;
use App\Models\User;
use App\Services\LibraryStatisticsService;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchLibraryStatisticsController extends Controller
{
    public function __construct(
        private readonly LibraryStatisticsService $libraryStatisticsService,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        return LibraryStatisticResource::collection($this->libraryStatisticsService->getStatistics($user));
    }
}
