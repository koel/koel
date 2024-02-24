<?php

namespace App\Http\Requests\API;

/** @property-read string $image */
class UploadArtistImageRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'image';
    }
}
