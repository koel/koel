<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\RadioStationRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class DeleteInternetRadioStationController extends Controller
{
    public function __construct(
        private readonly RadioStationRepository $radioStationRepository,
    ) {}

    /** @param User $user */
    public function __invoke(IdRequest $request, Authenticatable $user)
    {
        $station = $this->radioStationRepository->findOneWithUserContext($request->id, $user);

        $this->authorize('delete', $station);

        $station->delete();

        return SubsonicResponse::ok();
    }
}
