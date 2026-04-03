<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DuplicateUploadResource;
use App\Models\User;
use App\Services\DuplicateUploadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use App\Http\Requests\API\DuplicateUploadRequest;

class DuplicateUploadsController extends Controller
{
    /** @param User $user */
    public function fetch(Request $request, DuplicateUploadService $service, Authenticatable $user)
    {
        return DuplicateUploadResource::collection(
            $service->findForUser($user, (int) $request->input('per_page', 50))
        );
    }

    /** @param User $user */
    public function keep(DuplicateUploadRequest $request, DuplicateUploadService $service, Authenticatable $user)
    {
        $service->keepDuplicateUploads($user, (array) $request->input('uploads'));
        return;
    }

    /** @param User $user */
    public function delete(DuplicateUploadRequest $request, DuplicateUploadService $service, Authenticatable $user)
    {
        $service->discardDuplicateUploads($user, (array) $request->input('uploads'));
        return;
    }
}
