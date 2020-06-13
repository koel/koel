<?php

namespace App\Http\Controllers\API;

use App\Events\MediaCacheObsolete;
use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Http\Requests\API\UploadRequest;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    private $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function store(UploadRequest $request): JsonResponse
    {
        try {
            $song = $this->uploadService->handleUploadedFile($request->file);
        } catch (MediaPathNotSetException $e) {
            abort(403, $e->getMessage());
        } catch (SongUploadFailedException $e) {
            abort(400, $e->getMessage());
        }

        event(new MediaCacheObsolete());

        return response()->json($song->load('album', 'artist'));
    }
}
