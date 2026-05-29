<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetAlbumList2Request;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

class GetAlbumList2Controller extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetAlbumList2Request $request, Authenticatable $user)
    {
        $size = $request->integer('size', 10);
        $offset = $request->integer('offset', 0);

        $albums = $this->loadAlbums($request, $size, $offset)->loadCount('songs')->loadSum('songs', 'length');

        return SubsonicResponse::ok([
            'albumList2' => [
                'album' => $albums->map(static fn (Album $album) => AlbumResource::toArray($album, $user))->all(),
            ],
        ]);
    }

    /** @return Collection<int, Album> */
    private function loadAlbums(GetAlbumList2Request $request, int $size, int $offset): Collection
    {
        return match ($request->type) {
            'newest' => $this->albumRepository->getRecentlyAdded($size),
            'frequent' => $this->albumRepository->getMostPlayed($size),
            'random' => $this->albumRepository->getRandom($size),
            'starred' => $this->albumRepository->getFavorites($size, $offset),
            'recent' => $this->albumRepository->getRecentlyPlayed($size),
            'byYear' => $this->albumRepository->getByYearRange(
                $request->integer('fromYear'),
                $request->integer('toYear'),
                $size,
                $offset,
            ),
            'alphabeticalByName' => $this->albumRepository->getOrdered('albums.name', 'asc', $size, $offset),
            default => throw new LogicException("Unsupported album list type: {$request->type}"),
        };
    }
}
