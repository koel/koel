<?php

namespace App\Http\Controllers\API;

use App\Events\LibraryChanged;
use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadRequest;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Services\UploadService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UploadController extends Controller
{
    /** @param User $user */
    public function __invoke(
        UploadService $uploadService,
        AlbumRepository $albumRepository,
        SongRepository $songRepository,
        UploadRequest $request,
        Authenticatable $user
    ) {
        $this->authorize('admin', User::class);

        try {
            $song = $songRepository->getOne($uploadService->handleUploadedFile($request->file)->id);

            event(new LibraryChanged());

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
