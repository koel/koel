<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\RadioStation;
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
                'internetRadioStation' => $stations->map(static fn (RadioStation $station) => [
                    'id' => $station->id,
                    'name' => $station->name,
                    'streamUrl' => $station->url,
                    'homepageUrl' => $station->homepage_url,
                ])->all(),
            ],
        ]);
    }
}
