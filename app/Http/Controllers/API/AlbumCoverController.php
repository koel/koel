<?php

namespace App\Http\Controllers\API;

use App\Events\LibraryChanged;
use App\Http\Requests\API\AlbumCoverUpdateRequest;
use App\Models\Album;
use App\Services\MediaMetadataService;
use Illuminate\Http\JsonResponse;

/**
 * @group 5. Media information
 */
class AlbumCoverController extends Controller
{
    private $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    /**
     * Upload an album's cover
     *
     * Upload an image as an album's cover.
     *
     * @bodyParam cover string required The cover image's content, in <a href="https://en.wikipedia.org/wiki/Data_URI_scheme">Data URI format</a>.
     *            Example: data:image/jpeg;base64,Rm9v
     * @responseFile responses/albumCover.update.json
     *
     * @return JsonResponse
     */
    public function update(AlbumCoverUpdateRequest $request, Album $album)
    {
        $this->mediaMetadataService->writeAlbumCover(
            $album,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        event(new LibraryChanged());

        return new JsonResponse(['coverUrl' => $album->cover]);
    }
}
