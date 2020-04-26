<?php

namespace App\Http\Requests\API;

/** @property-read string $cover */
class AlbumCoverUpdateRequest extends AbstractMediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'cover';
    }
}
