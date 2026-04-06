<?php

namespace App\Http\Controllers\API\Upload;

use App\Http\Controllers\Controller;
use App\Http\Resources\DuplicateUploadResource;
use App\Models\User;
use App\Repositories\DuplicateUploadRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchDuplicateUploadsController extends Controller
{
    /** @param User $user */
    public function __invoke(DuplicateUploadRepository $repository, Authenticatable $user)
    {
        return DuplicateUploadResource::collection($repository->getAllForUser($user));
    }
}
