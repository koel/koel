<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

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
            'streamUrl' => ['required', 'string'],
            'name' => ['required', 'string'],
            'homepageUrl' => ['nullable', 'string'],
        ];
    }
}
