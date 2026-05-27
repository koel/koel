<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;

class GetCoverArtController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $filename = $this->resolveImageFilename($request->id);
        $path = $filename ? image_storage_path($filename, ensureDirectoryExists: false) : null;

        if (!$path || !File::isFile($path)) {
            return SubsonicResponse::error(70, 'Cover art not found.');
        }

        return response()->file($path);
    }

    private function resolveImageFilename(string $id): ?string
    {
        try {
            return $this->albumRepository->getOne($id)->cover;
        } catch (ModelNotFoundException) {
            return $this->artistRepository->getOne($id)->image;
        }
    }
}
