<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\RadioStationResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\RadioStationRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetInternetRadioStationsController extends Controller
{
    public function __construct(
        private readonly RadioStationRepository $radioStationRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $stations = $this->radioStationRepository->getAllForUser($user);

        return SubsonicResponse::ok([
            'internetRadioStations' => [
                'internetRadioStation' => $stations->map(RadioStationResource::toArray(...))->all(),
            ],
        ]);
    }
}
