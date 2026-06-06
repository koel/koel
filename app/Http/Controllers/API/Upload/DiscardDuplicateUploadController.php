<?php

namespace App\Http\Controllers\API\Upload;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Models\DuplicateUpload;
use App\Services\Upload\DuplicateUploadService;

#[DisabledInDemo]
class DiscardDuplicateUploadController extends Controller
{
    public function __invoke(DuplicateUpload $duplicateUpload, DuplicateUploadService $service)
    {
        $this->authorize('own', $duplicateUpload);

        $service->discard(collect([$duplicateUpload]));

        return response()->noContent();
    }
}
