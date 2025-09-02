<?php

namespace App\Http\Requests\API\Artist;

use App\Http\Requests\API\MediaImageUpdateRequest;

/** @property-read string $image */
class ArtistImageStoreRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'image';
    }
}
