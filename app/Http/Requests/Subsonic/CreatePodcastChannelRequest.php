<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $url
 */
class CreatePodcastChannelRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url'],
        ];
    }
}
