<?php

namespace App\Http\Controllers\API\Upload;

use App\Attributes\DisabledInDemo;
use App\Exceptions\MediaPathNotSetException;
use App\Facades\Dispatcher;
use App\Helpers\Ulid;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Upload\UploadSongRequest;
use App\Jobs\HandleSongUploadJob;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadResponse;
use App\Services\SongStorages\SongStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\Response;

#[DisabledInDemo]
class UploadSongController extends Controller
{
    /** @param User $user */
    public function __invoke(
        SongStorage $storage,
        AlbumRepository $albumRepository,
        SongRepository $songRepository,
        UploadSongRequest $request,
        Authenticatable $user,
    ) {
        $this->authorize('upload', User::class);
        $storage->assertSupported();

        try {
            $file = $request->file->move(
                artifact_path('tmp/' . Ulid::generate()),
                $request->file->getClientOriginalName(),
            );

            /** @var Song|PendingDispatch $dispatchedResult */
            $dispatchedResult = Dispatcher::dispatch(new HandleSongUploadJob($file->getRealPath(), $user));
        } catch (MediaPathNotSetException $e) {
            abort(Response::HTTP_FORBIDDEN, $e->getMessage());
        }

        if (!$dispatchedResult instanceof Song) {
            return response()->noContent(Response::HTTP_ACCEPTED);
        }

        $song = $songRepository->getOne($dispatchedResult->id);
        $album = $albumRepository->getOne($song->album_id);

        return SongUploadResponse::make(song: $song, album: $album)->toResponse();
    }
}
