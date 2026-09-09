<?php

namespace App\Http\Controllers;

use App\Services\PwaManifestService;

class AppManifestController extends Controller
{
    public function __invoke(PwaManifestService $manifestService)
    {
        return response()
            ->json($manifestService->getAppManifest())
            ->header('Content-Type', 'application/manifest+json');
    }
}
