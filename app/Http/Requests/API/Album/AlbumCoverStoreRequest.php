<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\MediaImageUpdateRequest;

/** @property-read string $cover */
class AlbumCoverStoreRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'cover';
    }
}
