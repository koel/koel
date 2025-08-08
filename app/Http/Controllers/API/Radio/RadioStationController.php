<?php

namespace App\Http\Controllers\API\Radio;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Radio\StoreRadioStationRequest;
use App\Http\Requests\API\Radio\UpdateRadioStationRequest;
use App\Http\Resources\RadioStationResource;
use App\Models\RadioStation;
use App\Models\User;
use App\Repositories\RadioStationRepository;
use App\Services\RadioService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class RadioStationController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly RadioStationRepository $repository,
        private readonly RadioService $radioService,
        private readonly Authenticatable $user,
    ) {
    }

    public function index()
    {
        return RadioStationResource::collection($this->repository->getAllForUser($this->user));
    }

    public function store(StoreRadioStationRequest $request)
    {
        $station = $this->radioService->createRadioStation(
            $request->url,
            $request->name,
            $request->logo,
            $request->description,
            (bool) $request->is_public,
            $this->user
        );

        return RadioStationResource::make($station)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(RadioStation $station, UpdateRadioStationRequest $request)
    {
        $this->authorize('update', $station);

        $updated = $this->radioService->updateRadioStation(
            $station,
            $request->url,
            $request->name,
            $request->logo,
            $request->description,
            (bool) $request->is_public
        );

        return RadioStationResource::make($updated);
    }

    public function destroy(RadioStation $station)
    {
        $this->authorize('delete', $station);

        $station->delete();

        return response()->noContent();
    }
}
