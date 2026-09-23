<?php

namespace App\Http\Controllers\API\Upload;

use App\Attributes\DisabledInDemo;
use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Facades\Dispatcher;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Upload\CompletePresignedUploadRequest;
use App\Http\Resources\DuplicateUploadResource;
use App\Jobs\HandlePresignedSongUploadJob;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadResponse;
use App\Services\SongStorages\Contracts\IssuesPresignedUploadUrls;
use App\Services\SongStorages\SongStorage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\Response;

#[DisabledInDemo]
class CompletePresignedUploadController extends Controller
{
    /** @param User $user */
    public function __invoke(
        SongStorage $storage,
        AlbumRepository $albumRepository,
        SongRepository $songRepository,
        CompletePresignedUploadRequest $request,
        Authenticatable $user,
    ) {
        $this->authorize('upload', User::class);
        $storage->assertSupported();

        abort_unless(
            $storage instanceof IssuesPresignedUploadUrls,
            Response::HTTP_NOT_IMPLEMENTED,
            'This storage does not support presigned uploads.',
        );

        abort_unless($storage->ownsUploadKey($request->key, $user), Response::HTTP_FORBIDDEN);

        try {
            /** @var Song|PendingDispatch $dispatchedResult */
            $dispatchedResult = Dispatcher::dispatch(
                new HandlePresignedSongUploadJob($storage->locationFromKey($request->key), $user),
            );

            if ($dispatchedResult instanceof Song) {
                $song = $songRepository->getOne($dispatchedResult->id);
                $album = $albumRepository->getOne($song->album_id);

                return SongUploadResponse::make(song: $song, album: $album)->toResponse();
            }

            return response()->noContent(Response::HTTP_ACCEPTED);
        } catch (DuplicateSongUploadException $e) {
            return response()->json(new DuplicateUploadResource($e->duplicateUpload), Response::HTTP_CONFLICT);
        } catch (SongUploadFailedException $e) {
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }
}
