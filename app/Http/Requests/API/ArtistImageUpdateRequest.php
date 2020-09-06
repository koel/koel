<?php

namespace App\Http\Requests\API;

/** @property string $image */
class ArtistImageUpdateRequest extends AbstractMediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'image';
    }
}
