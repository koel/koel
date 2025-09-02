<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Album\AlbumCoverStoreRequest;
use App\Models\Album;
use App\Services\AlbumService;
use App\Services\ArtworkService;

class AlbumCoverController extends Controller
{
    public function __construct(
        private readonly ArtworkService $artworkService,
        private readonly AlbumService $albumService,
    ) {
    }

    public function store(AlbumCoverStoreRequest $request, Album $album)
    {
        $this->authorize('update', $album);
        $this->artworkService->storeAlbumCover($album, $request->getFileContent());

        return response()->json(['cover_url' => $album->cover]);
    }

    public function destroy(Album $album)
    {
        $this->authorize('update', $album);
        $this->albumService->removeAlbumCover($album);

        return response()->noContent();
    }
}
