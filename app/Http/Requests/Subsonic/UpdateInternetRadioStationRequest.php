<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use App\Rules\HasAudioContentType;
use App\Rules\SafeUrl;

/**
 * @property string $id
 * @property string $streamUrl
 * @property string $name
 * @property ?string $homepageUrl
 */
class UpdateInternetRadioStationRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'streamUrl' => ['bail', 'required', 'url', new SafeUrl(), new HasAudioContentType()],
            'name' => ['required', 'string'],
            'homepageUrl' => ['nullable', 'string'],
        ];
    }
}
