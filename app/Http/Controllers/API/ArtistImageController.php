<?php

namespace App\Http\Controllers\API;

use App\Events\LibraryChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ArtistImageUpdateRequest;
use App\Models\Artist;
use App\Services\MediaMetadataService;
use Illuminate\Http\JsonResponse;

class ArtistImageController extends Controller
{
    public function __construct(private MediaMetadataService $mediaMetadataService)
    {
    }

    public function update(ArtistImageUpdateRequest $request, Artist $artist)
    {
        $this->mediaMetadataService->writeArtistImage(
            $artist,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        event(new LibraryChanged());

        return new JsonResponse(['imageUrl' => $artist->image]);
    }
}
