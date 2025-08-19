<?php

namespace App\Http\Controllers\API\Radio;

use App\Http\Controllers\Controller;
use App\Models\RadioStation;
use App\Services\RadioService;

class RadioStationLogoController extends Controller
{
    public function __construct(private readonly RadioService $radioService)
    {
    }

    public function destroy(RadioStation $radioStation)
    {
        $this->authorize('update', $radioStation);
        $this->radioService->removeStationLogo($radioStation);

        return response()->noContent();
    }
}
