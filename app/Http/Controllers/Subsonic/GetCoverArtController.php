<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\Subsonic\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PlaylistRepository;
use App\Repositories\PodcastRepository;
use App\Services\Image\ImageStorage;

class GetCoverArtController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly PodcastRepository $podcastRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly ImageStorage $imageStorage,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $album = $this->albumRepository->findOne($request->id);
        $artist = $album ? null : $this->artistRepository->findOne($request->id);
        $podcast = $album || $artist ? null : $this->podcastRepository->findOne($request->id);
        $playlist = $album || $artist || $podcast ? null : $this->playlistRepository->findOne($request->id);

        throw_if(!$album && !$artist && !$podcast && !$playlist, DataNotFoundException::class, 'Cover art not found.');

        if ($podcast?->image) {
            return redirect($podcast->image);
        }

        if ($playlist) {
            $this->authorize('access', $playlist);
        }

        $fileName = match (true) {
            (bool) $album => $album->cover,
            (bool) $artist => $artist->image ?: $artist->album_cover,
            default => $playlist?->cover,
        };

        if ($this->imageStorage->exists($fileName)) {
            $response = ImageStorage::disk()->response($fileName)->setEtag($fileName);
            $response->isNotModified($request);

            return $response;
        }

        return response()->file(resource_path('assets/img/covers/default.png'));
    }
}
