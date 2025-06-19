<?php

namespace App\Http\Controllers\API;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadRequest;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Services\SongStorages\SongStorage;
use App\Services\UploadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UploadController extends Controller
{
    /** @param User $user */
    public function __invoke(
        SongStorage $storage,
        UploadService $uploadService,
        AlbumRepository $albumRepository,
        SongRepository $songRepository,
        UploadRequest $request,
        Authenticatable $user
    ) {
        $this->authorize('upload', User::class);
        $storage->assertSupported();

        try {
            $song = $songRepository->getOne($uploadService->handleUpload($request->file, $user)->id);

            return response()->json([
                'song' => SongResource::make($song),
                'album' => AlbumResource::make($albumRepository->getOne($song->album_id)),
            ]);
        } catch (MediaPathNotSetException $e) {
            abort(Response::HTTP_FORBIDDEN, $e->getMessage());
        } catch (SongUploadFailedException $e) {
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }
}
