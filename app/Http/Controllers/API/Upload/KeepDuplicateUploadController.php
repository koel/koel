<?php

namespace App\Http\Controllers\API\Upload;

use App\Http\Controllers\Controller;
use App\Models\DuplicateUpload;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadResponse;
use App\Services\DuplicateUploadService;

class KeepDuplicateUploadController extends Controller
{
    public function __invoke(
        DuplicateUpload $duplicateUpload,
        DuplicateUploadService $service,
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
    ) {
        $this->authorize('own', $duplicateUpload);

        $songs = $service->keep(collect([$duplicateUpload]));
        $song = $songRepository->getOne($songs[0]->id);
        $album = $albumRepository->getOne($song->album_id);

        return SongUploadResponse::make(song: $song, album: $album)->toResponse();
    }
}
