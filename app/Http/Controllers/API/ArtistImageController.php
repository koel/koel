<?php

namespace App\Http\Controllers\API;

use App\Events\LibraryChanged;
use App\Http\Requests\API\ArtistImageUpdateRequest;
use App\Models\Artist;
use App\Services\MediaMetadataService;
use Illuminate\Http\JsonResponse;

/**
 * @group 5. Media information
 */
class ArtistImageController extends Controller
{
    private $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    /**
     * Upload an artist's image
     *
     * Upload an image as an artist's image.
     *
     * @bodyParam image string required The image's content, in <a href="https://en.wikipedia.org/wiki/Data_URI_scheme">Data URI format</a>.
     *            Example: data:image/jpeg;base64,Rm9v
     * @responseFile responses/artistImage.update.json
     *
     * @return JsonResponse
     */
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
