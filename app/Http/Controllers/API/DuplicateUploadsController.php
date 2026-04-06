<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\DuplicateUploadRequest;
use App\Http\Resources\DuplicateUploadResource;
use App\Models\User;
use App\Services\DuplicateUploadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DuplicateUploadsController extends Controller
{
    /** @param User $user */
    public function fetch(
        Request $request,
        DuplicateUploadService $service,
        Authenticatable $user,
    ): AnonymousResourceCollection {
        return DuplicateUploadResource::collection($service->findForUser($user, (int) $request->input('per_page', 50)));
    }

    /** @param User $user */
    public function keep(
        DuplicateUploadRequest $request,
        DuplicateUploadService $service,
        Authenticatable $user,
    ): Response {
        $service->keepDuplicateUploads($user, (array) $request->input('uploads'));

        return response()->noContent();
    }

    /** @param User $user */
    public function delete(
        DuplicateUploadRequest $request,
        DuplicateUploadService $service,
        Authenticatable $user,
    ): Response {
        $service->discardDuplicateUploads($user, (array) $request->input('uploads'));

        return response()->noContent();
    }
}
