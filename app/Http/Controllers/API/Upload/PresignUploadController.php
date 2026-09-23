<?php

namespace App\Http\Controllers\API\Upload;

use App\Attributes\DisabledInDemo;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Upload\PresignUploadRequest;
use App\Models\User;
use App\Services\SongStorages\Contracts\IssuesPresignedUploadUrls;
use App\Services\SongStorages\SongStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

#[DisabledInDemo]
class PresignUploadController extends Controller
{
    /** @param User $user */
    public function __invoke(SongStorage $storage, PresignUploadRequest $request, Authenticatable $user)
    {
        $this->authorize('upload', User::class);
        $storage->assertSupported();

        abort_unless(
            $storage instanceof IssuesPresignedUploadUrls,
            Response::HTTP_NOT_IMPLEMENTED,
            'This storage does not support presigned uploads.',
        );

        return response()->json($storage->presignUpload($request->file_name, $user)->toArray());
    }
}
