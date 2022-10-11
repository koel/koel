<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SongUpdateRequest;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Services\LibraryManager;
use App\Services\SongService;
use App\Values\SongUpdateData;

class SongController extends Controller
{
    public function __construct(
        private SongService $songService,
        private AlbumRepository $albumRepository,
        private ArtistRepository $artistRepository,
        private LibraryManager $libraryManager
    ) {
    }

    public function update(SongUpdateRequest $request)
    {
        $updatedSongs = $this->songService->updateSongs($request->songs, SongUpdateData::fromRequest($request));
        $albums = $this->albumRepository->getByIds($updatedSongs->pluck('album_id')->toArray());

        $artists = $this->artistRepository->getByIds(
            array_merge(
                $updatedSongs->pluck('artist_id')->all(),
                $updatedSongs->pluck('album_artist_id')->all()
            )
        );

        return response()->json([
            'songs' => SongResource::collection($updatedSongs),
            'albums' => AlbumResource::collection($albums),
            'artists' => ArtistResource::collection($artists),
            'removed' => $this->libraryManager->prune(),
        ]);
    }
}
