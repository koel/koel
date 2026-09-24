<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\Subsonic\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PodcastRepository;
use App\Services\Image\ImageStorage;

class GetCoverArtController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly PodcastRepository $podcastRepository,
        private readonly ImageStorage $imageStorage,
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

        $fileName = $album ? $album->cover : $artist?->image;

        if ($this->imageStorage->exists($fileName)) {
            return ImageStorage::disk()->response($fileName);
        }

        return response()->file(resource_path('assets/img/covers/default.png'));
    }
}
