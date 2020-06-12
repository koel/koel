<?php

namespace App\Http\Controllers\API;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Illuminate\Http\JsonResponse;

/**
 * @group 5. Media information
 */
class AlbumThumbnailController extends Controller
{
    private $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    /**
     * Get an album's thumbnail
     *
     * Get an album's thumbnail (a 48px-wide blurry version of the album's cover).
     * Returns the full URL to the thumbnail or NULL if the album has no cover.
     *
     * @response ["thumbnailUrl", "https://localhost/public/img/covers/a146d01afb742b01f28ab8b556f9a75d_thumbnail.jpg"]
     * @return JsonResponse
     */
    public function get(Album $album): JsonResponse
    {
        return response()->json(['thumbnailUrl' => $this->mediaMetadataService->getAlbumThumbnailUrl($album)]);
    }
}
