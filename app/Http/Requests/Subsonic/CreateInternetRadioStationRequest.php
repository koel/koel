<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use App\Rules\HasAudioContentType;
use App\Rules\SafeUrl;

/**
 * @property string $streamUrl
 * @property string $name
 * @property ?string $homepageUrl
 */
class CreateInternetRadioStationRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'streamUrl' => ['required', 'url', new SafeUrl(), new HasAudioContentType()],
            'name' => ['required', 'string'],
            'homepageUrl' => ['nullable', 'string'],
        ];
    }
}
