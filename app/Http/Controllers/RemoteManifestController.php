<?php

namespace App\Http\Controllers;

use App\Services\PwaManifestService;

class RemoteManifestController extends Controller
{
    public function __invoke(PwaManifestService $manifestService)
    {
        return response()
            ->json($manifestService->getRemoteManifest())
            ->header('Content-Type', 'application/manifest+json');
    }
}
