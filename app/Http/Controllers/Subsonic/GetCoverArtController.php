<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
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
        $filename = $this->resolveImageFilename($request->id);
        $path = $filename ? image_storage_path($filename, ensureDirectoryExists: false) : null;

        if ($path && File::isFile($path)) {
            return response()->file($path);
        }

        return response()->file(resource_path('assets/img/covers/default.png'));
    }

    private function resolveImageFilename(string $id): ?string
    {
        $album = $this->albumRepository->findOne($id);

        if ($album) {
            return $album->cover;
        }

        return $this->artistRepository->findOne($id)?->image;
    }
}
