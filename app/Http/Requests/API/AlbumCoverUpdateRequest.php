<?php

namespace App\Http\Requests\API;

/** @property string $cover */
class AlbumCoverUpdateRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'cover';
    }
}
