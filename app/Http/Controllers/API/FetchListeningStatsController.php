<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Statistics\ListeningStatsRequest;
use App\Http\Resources\ListeningStatsResource;
use App\Models\User;
use App\Services\ListeningStatsService;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchListeningStatsController extends Controller
{
    public function __construct(
        private readonly ListeningStatsService $listeningStatsService,
    ) {}

    /** @param User $user */
    public function __invoke(ListeningStatsRequest $request, Authenticatable $user)
    {
        return ListeningStatsResource::make($this->listeningStatsService->getStats(
            $user,
            $request->period(),
            $request->timezone(),
        ));
    }
}
