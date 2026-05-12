<?php

namespace App\Http\Controllers\API\Upload;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\DuplicateUploadRepository;
use App\Services\Upload\DuplicateUploadService;
use Illuminate\Contracts\Auth\Authenticatable;

class DiscardAllDuplicateUploadsController extends Controller
{
    /** @param User $user */
    public function __invoke(
        DuplicateUploadRepository $repository,
        DuplicateUploadService $service,
        Authenticatable $user,
    ) {
        $service->discard($repository->getAllForUser($user));

        return response()->noContent();
    }
}
