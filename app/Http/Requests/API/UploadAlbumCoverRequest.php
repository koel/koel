<?php

namespace App\Http\Requests\API;

/** @property-read string $cover */
class UploadAlbumCoverRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'cover';
    }
}
