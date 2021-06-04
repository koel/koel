<?php

namespace App\Http\Controllers\API;

use App\Events\MediaCacheObsolete;
use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Http\Requests\API\UploadRequest;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UploadController extends Controller
{
    private UploadService $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function store(UploadRequest $request): JsonResponse
    {
        try {
            $song = $this->uploadService->handleUploadedFile($request->file);
        } catch (MediaPathNotSetException $e) {
            abort(Response::HTTP_FORBIDDEN, $e->getMessage());
        } catch (SongUploadFailedException $e) {
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        event(new MediaCacheObsolete());

        return response()->json($song->load('album', 'artist'));
    }
}
