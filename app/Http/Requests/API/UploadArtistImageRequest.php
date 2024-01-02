<?php

namespace App\Http\Requests\API;

/** @property string $image */
class UploadArtistImageRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'image';
    }
}
