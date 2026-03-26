<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DuplicateUploadResource;
use App\Models\User;
use App\Services\DuplicateUploadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FetchDuplicateUploadsController extends Controller
{
    /** @param User $user */
    public function __invoke(Request $request, DuplicateUploadService $service, Authenticatable $user): AnonymousResourceCollection
    {
        return DuplicateUploadResource::collection(
            $service->findForUser($user, (int) $request->input('per_page', 50))
        );
    }
}
