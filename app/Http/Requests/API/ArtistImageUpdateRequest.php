<?php

namespace App\Http\Requests\API;

/** @property string $image */
class ArtistImageUpdateRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'image';
    }
}
