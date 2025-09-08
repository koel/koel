<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Album\AlbumCoverStoreRequest;
use App\Models\Album;
use App\Services\AlbumService;
use App\Services\ImageStorage;

class AlbumCoverController extends Controller
{
    public function __construct(
        private readonly ImageStorage $imageStorage,
        private readonly AlbumService $albumService,
    ) {
    }

    public function store(AlbumCoverStoreRequest $request, Album $album)
    {
        $this->authorize('update', $album);
        $this->imageStorage->storeAlbumCover($album, $request->getFileContent());

        return response()->json(['cover_url' => $album->cover]);
    }

    public function destroy(Album $album)
    {
        $this->authorize('update', $album);
        $this->albumService->removeAlbumCover($album);

        return response()->noContent();
    }
}
