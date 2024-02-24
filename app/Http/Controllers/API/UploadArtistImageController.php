<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadArtistImageRequest;
use App\Models\Artist;
use App\Services\MediaMetadataService;

class UploadArtistImageController extends Controller
{
    public function __invoke(
        UploadArtistImageRequest $request,
        Artist $artist,
        MediaMetadataService $mediaMetadataService
    ) {
        $this->authorize('update', $artist);

        $mediaMetadataService->writeArtistImage(
            $artist,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        return response()->json(['image_url' => $artist->image]);
    }
}
