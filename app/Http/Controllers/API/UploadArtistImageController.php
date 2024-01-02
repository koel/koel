<?php

namespace App\Http\Controllers\API;

use App\Events\LibraryChanged;
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
        $mediaMetadataService->writeArtistImage(
            $artist,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        event(new LibraryChanged());

        return response()->json(['imageUrl' => $artist->image]);
    }
}
