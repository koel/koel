<?php

namespace App\Http\Requests\API\Playlist;

use App\Http\Requests\API\MediaImageUpdateRequest;

/** @property-read string $cover */
class PlaylistCoverUpdateRequest extends MediaImageUpdateRequest
{
    protected function getImageFieldName(): string
    {
        return 'cover';
    }
}
