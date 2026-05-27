<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetAlbumList2Request;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

class GetAlbumList2Controller extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {}

    public function __invoke(GetAlbumList2Request $request)
    {
        $size = $request->integer('size', 10);
        $offset = $request->integer('offset', 0);

        $albums = $this->loadAlbums($request->type, $size, $offset)->loadCount('songs')->loadSum('songs', 'length');

        return SubsonicResponse::ok([
            'albumList2' => [
                'album' => $albums->map(AlbumResource::toArray(...))->all(),
            ],
        ]);
    }

    /** @return Collection<int, Album> */
    private function loadAlbums(string $type, int $size, int $offset): Collection
    {
        return match ($type) {
            'newest' => $this->albumRepository->getRecentlyAdded($size),
            'frequent' => $this->albumRepository->getMostPlayed($size),
            'random' => $this->albumRepository->getRandom($size),
            'starred' => $this->albumRepository->getFavorites(),
            'alphabeticalByName' => $this->albumRepository->getOrdered('albums.name', 'asc', $size, $offset),
            default => throw new LogicException("Unsupported album list type: {$type}"),
        };
    }
}
