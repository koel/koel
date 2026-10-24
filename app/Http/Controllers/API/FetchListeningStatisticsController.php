<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Statistics\ListeningStatisticsRequest;
use App\Http\Resources\ListeningStatisticsResource;
use App\Models\User;
use App\Services\ListeningStatisticsService;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchListeningStatisticsController extends Controller
{
    public function __construct(
        private readonly ListeningStatisticsService $listeningStatisticsService,
    ) {}

    /** @param User $user */
    public function __invoke(ListeningStatisticsRequest $request, Authenticatable $user)
    {
        return ListeningStatisticsResource::make($this->listeningStatisticsService->getStatistics(
            $user,
            $request->period(),
            $request->timezone(),
        ));
    }
}
