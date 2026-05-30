<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\CreateInternetRadioStationRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\RadioService;
use App\Values\Radio\RadioStationCreateData;
use Illuminate\Contracts\Auth\Authenticatable;

class CreateInternetRadioStationController extends Controller
{
    public function __construct(
        private readonly RadioService $radioService,
    ) {}

    /** @param User $user */
    public function __invoke(CreateInternetRadioStationRequest $request, Authenticatable $user)
    {
        $this->radioService->createRadioStation(
            RadioStationCreateData::make(
                url: $request->streamUrl,
                name: $request->name,
                description: '',
                homepageUrl: $request->homepageUrl,
            ),
            $user,
        );

        return SubsonicResponse::ok();
    }
}
