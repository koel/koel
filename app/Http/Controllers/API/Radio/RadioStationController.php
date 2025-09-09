<?php

namespace App\Http\Controllers\API\Radio;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Radio\RadioStationStoreRequest;
use App\Http\Requests\API\Radio\RadioStationUpdateRequest;
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

    #[DisabledInDemo]
    public function store(RadioStationStoreRequest $request)
    {
        $station = $this->radioService->createRadioStation($request->toDto(), $this->user);

        return RadioStationResource::make($station)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[DisabledInDemo]
    public function update(RadioStation $station, RadioStationUpdateRequest $request)
    {
        $this->authorize('update', $station);

        return RadioStationResource::make($this->radioService->updateRadioStation($station, $request->toDto()));
    }

    #[DisabledInDemo]
    public function destroy(RadioStation $station)
    {
        $this->authorize('delete', $station);

        $station->delete();

        return response()->noContent();
    }
}
