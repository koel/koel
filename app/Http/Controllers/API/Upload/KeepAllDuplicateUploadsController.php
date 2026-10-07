<?php

namespace App\Http\Controllers\API\Upload;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\User;
use App\Repositories\DuplicateUploadRepository;
use App\Responses\SongUploadResponse;
use App\Services\Upload\DuplicateUploadService;
use App\Services\Upload\UploadService;
use Illuminate\Contracts\Auth\Authenticatable;

#[DisabledInDemo]
class KeepAllDuplicateUploadsController extends Controller
{
    /** @param User $user */
    public function __invoke(
        DuplicateUploadRepository $repository,
        DuplicateUploadService $service,
        UploadService $uploadService,
        Authenticatable $user,
    ) {
        return $service->keep($repository->getAllForUser($user))->map(
            static fn (Song $song): SongUploadResponse => $uploadService->makeUploadResponse($song, $user),
        );
    }
}
