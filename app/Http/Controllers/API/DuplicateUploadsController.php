<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DuplicateUploadResource;
use App\Models\User;
use App\Services\DuplicateUploadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

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
    public function keep(Request $request, DuplicateUploadService $service, Authenticatable $user)
    {
        return;
    }

    /** @param User $user */
    public function delete(Request $request, DuplicateUploadService $service, Authenticatable $user)
    {
        return;
    }
}
