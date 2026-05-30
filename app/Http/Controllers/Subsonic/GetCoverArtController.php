<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\Subsonic\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PodcastRepository;
use Illuminate\Support\Facades\File;

class GetCoverArtController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly PodcastRepository $podcastRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $album = $this->albumRepository->findOne($request->id);
        $artist = $album ? null : $this->artistRepository->findOne($request->id);
        $podcast = $album || $artist ? null : $this->podcastRepository->findOne($request->id);

        throw_if(!$album && !$artist && !$podcast, DataNotFoundException::class, 'Cover art not found.');

        if ($podcast?->image) {
            return redirect($podcast->image);
        }

        $filename = $album ? $album->cover : $artist?->image;
        $path = $filename ? image_storage_path($filename, ensureDirectoryExists: false) : null;

        if ($path && File::isFile($path)) {
            return response()->file($path);
        }

        return response()->file(resource_path('assets/img/covers/default.png'));
    }
}
