<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadAlbumCoverRequest;
use App\Models\Album;
use App\Services\ArtworkService;
use Illuminate\Support\Facades\Cache;

class UploadAlbumCoverController extends Controller
{
    public function __invoke(UploadAlbumCoverRequest $request, Album $album, ArtworkService $metadataService)
    {
        $this->authorize('update', $album);
        $metadataService->storeAlbumCover($album, $request->getFileContent());

        Cache::delete("album.info.{$album->id}.{$album->name}");

        return response()->json(['cover_url' => $album->cover]);
    }
}
