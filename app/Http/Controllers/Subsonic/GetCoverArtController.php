<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Support\Facades\File;

class GetCoverArtController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $album = $this->albumRepository->findOne($request->id);
        $artist = $album ? null : $this->artistRepository->findOne($request->id);

        if (!$album && !$artist) {
            return SubsonicResponse::error(70, 'Cover art not found.');
        }

        $filename = $album ? $album->cover : $artist->image;
        $path = $filename ? image_storage_path($filename, ensureDirectoryExists: false) : null;

        if ($path && File::isFile($path)) {
            return response()->file($path);
        }

        return response()->file(resource_path('assets/img/covers/default.png'));
    }
}
