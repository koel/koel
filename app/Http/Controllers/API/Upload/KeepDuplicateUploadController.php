<?php

namespace App\Http\Controllers\API\Upload;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Models\DuplicateUpload;
use App\Models\User;
use App\Services\Upload\DuplicateUploadService;
use App\Services\Upload\UploadService;
use Illuminate\Contracts\Auth\Authenticatable;

#[DisabledInDemo]
class KeepDuplicateUploadController extends Controller
{
    /** @param User $user */
    public function __invoke(
        DuplicateUpload $duplicateUpload,
        DuplicateUploadService $service,
        UploadService $uploadService,
        Authenticatable $user,
    ) {
        $this->authorize('own', $duplicateUpload);

        $songs = $service->keep(collect([$duplicateUpload]));

        return $uploadService->makeUploadResponse($songs[0], $user)->toResponse();
    }
}
