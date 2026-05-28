<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class GetCoverArtController extends Controller
{
    private const string PLACEHOLDER_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

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

        return self::placeholderResponse();
    }

    private function resolveImageFilename(string $id): ?string
    {
        try {
            return $this->albumRepository->getOne($id)->cover;
        } catch (ModelNotFoundException) {
            return $this->artistRepository->getOne($id)->image;
        }
    }

    private static function placeholderResponse(): Response
    {
        return response(base64_decode(self::PLACEHOLDER_PNG_BASE64, true), 200, ['Content-Type' => 'image/png']);
    }
}
