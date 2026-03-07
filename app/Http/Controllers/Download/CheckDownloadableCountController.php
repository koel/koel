<?php

namespace App\Http\Controllers\Download;

use App\Enums\DownloadableType;
use App\Exceptions\DownloadLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Download\CheckDownloadableCountRequest;
use App\Models\User;
use App\Services\DownloadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class CheckDownloadableCountController extends Controller
{
    /** @param User $user */
    public function __invoke(CheckDownloadableCountRequest $request, DownloadService $service, Authenticatable $user)
    {
        try {
            $type = $request->downloadableType();

            $service->assertWithinDownloadLimit(
                type: $type,
                user: $user,
                id: $type === DownloadableType::Songs ? $request->ids : $request->id,
            );

            return response()->noContent();
        } catch (DownloadLimitExceededException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }
}
