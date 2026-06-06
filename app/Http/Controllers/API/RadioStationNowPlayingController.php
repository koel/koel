<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RadioStation;
use App\Models\User;
use App\Services\Radio\RadioStreamMetadata;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class RadioStationNowPlayingController extends Controller
{
    /**
     * @param User $user
     */
    public function __invoke(Authenticatable $user, RadioStation $radioStation): JsonResponse
    {
        $this->authorize('access', $radioStation);

        return response()->json(RadioStreamMetadata::getCached($radioStation));
    }
}
