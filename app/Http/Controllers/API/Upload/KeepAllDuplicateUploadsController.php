<?php

namespace App\Http\Controllers\API\Upload;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\DuplicateUploadRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadResponse;
use App\Services\DuplicateUploadService;
use Illuminate\Contracts\Auth\Authenticatable;

class KeepAllDuplicateUploadsController extends Controller
{
    /** @param User $user */
    public function __invoke(
        DuplicateUploadRepository $repository,
        DuplicateUploadService $service,
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
        Authenticatable $user,
    ) {
        return $service->keep($repository->getAllForUser($user))->map(function (Song $song) use (
            $songRepository,
            $albumRepository,
        ): SongUploadResponse {
            $populatedSong = $songRepository->getOne($song->id);
            $album = $albumRepository->getOne($populatedSong->album_id);

            return SongUploadResponse::make(song: $populatedSong, album: $album);
        });
    }
}
