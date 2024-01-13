<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ActivateLicenseRequest;
use App\Models\License;
use App\Services\License\LicenseServiceInterface;

class ActivateLicenseController extends Controller
{
    public function __invoke(ActivateLicenseRequest $request, LicenseServiceInterface $licenseService)
    {
        $this->authorize('activate', License::class);

        $licenseService->activate($request->key);

        return response()->noContent();
    }
}
