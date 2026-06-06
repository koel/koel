<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\UpdateInternetRadioStationRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\RadioStationRepository;
use App\Services\RadioService;
use App\Values\Radio\RadioStationUpdateData;
use Illuminate\Contracts\Auth\Authenticatable;

class UpdateInternetRadioStationController extends Controller
{
    public function __construct(
        private readonly RadioService $radioService,
        private readonly RadioStationRepository $radioStationRepository,
    ) {}

    /** @param User $user */
    public function __invoke(UpdateInternetRadioStationRequest $request, Authenticatable $user)
    {
        $station = $this->radioStationRepository->findOneWithUserContext($request->id, $user);

        $this->authorize('update', $station);

        $this->radioService->updateRadioStation($station, RadioStationUpdateData::make(
            name: $request->name,
            url: $request->streamUrl,
            description: $station->description ?? '',
            logo: $station->logo,
            isPublic: $station->is_public,
            homepageUrl: $request->homepageUrl ?? $station->homepage_url,
        ));

        return SubsonicResponse::ok();
    }
}
