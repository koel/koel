<?php

namespace App\Http\Requests\API;

/**
 * @property-read bool|string|null $transcode
 */
class FetchSongPlayUrlRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return ['transcode' => ['sometimes', 'boolean']];
    }
}
