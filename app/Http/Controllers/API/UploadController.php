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
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UploadController extends Controller
{
    /** @param User $user */
    public function __invoke(
        SongStorage $storage,
        AlbumRepository $albumRepository,
        SongRepository $songRepository,
        UploadRequest $request,
        Authenticatable $user
    ) {
        $this->authorize('upload', User::class);
        $storage->assertSupported();

        try {
            // @todo decouple Song from storage, as storages should not be responsible for creating a song.
            $song = $songRepository->getOne($storage->storeUploadedFile($request->file, $user)->id, $user);

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
